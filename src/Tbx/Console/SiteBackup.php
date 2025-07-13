<?php
namespace Tbx\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Tk\Config;
use Tk\Encrypt;
use Tk\Log;
use Tk\Uri;

/**
 * List through all the site in the $config['tk.mirror.sites'] array
 * and backup their DB nightly and data weekly to a defined backup folder
 *
 * @author Tropotek <https://tropotek.com/>
 */
class SiteBackup extends Iface
{

    const string MIRROR_DB     = 'db';
    const string MIRROR_DATA   = 'file';

    protected bool   $dataMode = false;
    protected string $site     = '';
    protected string $destPath = '';

    protected function configure(): void
    {
        $this->setName('site-backup')
            ->setAliases(['sb'])
            ->addOption('list', 'L', InputOption::VALUE_NONE, 'List available sites to backup.')
            ->addOption('data', 'D', InputOption::VALUE_NONE, 'Mirror data files not DB')
            ->addArgument('site', InputArgument::OPTIONAL, 'Name of the site in $config[\'tk.mirror.sites\'] to backup.', '')
            ->addArgument('destPath', InputArgument::OPTIONAL, 'Specify a destination backup file path.', getcwd())
            ->setDescription('Backup a site DB or data files defined in $config[\'tk.mirror.sites\']');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->dataMode = $input->getOption('data');
        $this->site     = $input->getArgument('site');
        $this->destPath = rtrim($input->getArgument('destPath'), '/\\');

        $sites = $this->getConfig()->get('tk.mirror.sites', []);

        if ($input->getOption('list') || empty($this->site)) {
            $sites = implode("\n  - ", array_keys($sites));
            $this->writeComment("Sites available to backup: \n  - $sites");
            return Command::SUCCESS;
        }

        $site = $sites[$this->site] ?? [];
        if (empty($site)) {
            $this->writeError("Sites not defined in \$config['tk.mirror.sites']: {$this->site}");
            return Command::FAILURE;
        }

        if (!is_dir($this->destPath) || !is_writable($this->destPath)) {
            $this->writeError("Destination path does not writable: $this->destPath");
            return Command::FAILURE;
        }

        $filename = sprintf('%s/%s-%s.%s',
            $this->destPath,
            $this->site,
            //date('Y-m-d_h-i-s'),
            date('Y-m-d_h-i-s'),
            $this->dataMode ? 'tgz' : 'sql.gz'
        );

        $url = Uri::create($site['url'] . '/util/mirror', [
            'a' => $this->dataMode ? self::MIRROR_DATA : self::MIRROR_DB,
            'u' => $site['encUsername'],
            'p' => $site['encPassword']
        ]);

        if (!$this->postRequest($url, $site['secret'], $filename)) {
            $this->writeError("Error downloading from mirror site");
            unlink($filename);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function postRequest(Uri $srcUrl, string $secret, string $filename): bool
    {
        $srcUrl = $srcUrl->withScheme('https');

        // convert query vals to post vals
        $query = $srcUrl->getQuery();
        $srcUrl->reset();

        $fp = fopen($filename, "w");
        if ($fp === false) {
            Log::error("Cannot save filename");
            return false;
        }
        $curl = curl_init($srcUrl->toString());
        if ($curl === false) {
            Log::error("Cannot open mirror Url");
            return false;
        }

        $opts = [
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => $query,
            CURLOPT_FILE           => $fp,
            CURLOPT_HTTPHEADER     => [
                "authorization-key: " . $secret,
            ],
        ];
        if (Config::isDev()) {
            $opts[CURLOPT_SSL_VERIFYHOST] = false;
            $opts[CURLOPT_SSL_VERIFYPEER] = false;
        }

        curl_setopt_array($curl, $opts);

        curl_exec($curl);
        $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if(curl_error($curl) || $responseCode != 200) {
            Log::error("Error downloading mirror: " . curl_error($curl));
            return false;
        }
        curl_close($curl);
        fclose($fp);

        return true;
    }

}
