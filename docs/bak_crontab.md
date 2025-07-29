


```
# m h  dom mon dow   command

# local live sites
*/10 *  * * *   ~/public_html/Live/tkbtc/bin/cmd cron > /dev/null 2>&1

# local dev sites
0 7  * * *  ~/public_html/Projects/tktask/bin/cmd cron


# Tk backup remote sites

# tkwiki (testing)
# Mirror DB
#*/5  *  * * *  ~/public_html/lib/tk-tools/bin/tk.php sb tkwiki /home/smb/Misc/_sitebak/tkwiki
# Clean DB history older than 7 files
#*/6  *  * * *  ~/public_html/lib/tk-tools/bin/tk.php bc -M 7 /home/smb/Misc/_sitebak/tkwiki sql.gz
# Mirror data files
#*/10 *  * * *  ~/public_html/lib/tk-tools/bin/tk.php sb -D tkwiki /home/smb/Misc/_sitebak/tkwiki
# Clean file history older than 2 files
#*/12 *  * * *  ~/public_html/lib/tk-tools/bin/tk.php bc -M 2 /home/smb/Misc/_sitebak/tkwiki tgz

# tkwiki
00 20  * * *  ~/public_html/lib/tk-tools/bin/tk.php sb tkwiki /home/smb/Misc/_sitebak/tkwiki
30 20  * * *  ~/public_html/lib/tk-tools/bin/tk.php bc -M 7 /home/smb/Misc/_sitebak/tkwiki sql.gz
00 20  * * 5  ~/public_html/lib/tk-tools/bin/tk.php sb -D tkwiki /home/smb/Misc/_sitebak/tkwiki
30 20  * * 5  ~/public_html/lib/tk-tools/bin/tk.php bc -M 2 /home/smb/Misc/_sitebak/tkwiki tgz

# tkbtc
00 1  * * *  ~/public_html/lib/tk-tools/bin/tk.php sb tkbtc /home/smb/Misc/_sitebak/tkbtc
30 1  * * *  ~/public_html/lib/tk-tools/bin/tk.php bc -M 7 /home/smb/Misc/_sitebak/tkbtc sql.gz
00 1  * * 5  ~/public_html/lib/tk-tools/bin/tk.php sb -D tkbtc /home/smb/Misc/_sitebak/tkbtc
30 1  * * 5  ~/public_html/lib/tk-tools/bin/tk.php bc -M 2 /home/smb/Misc/_sitebak/tkbtc tgz

# tktask
00 2  * * *  ~/public_html/lib/tk-tools/bin/tk.php sb tktask /home/smb/Misc/_sitebak/tktask
30 2  * * *  ~/public_html/lib/tk-tools/bin/tk.php bc -M 7 /home/smb/Misc/_sitebak/tktask sql.gz
00 2  * * 5  ~/public_html/lib/tk-tools/bin/tk.php sb -D tktask /home/smb/Misc/_sitebak/tktask
30 2  * * 5  ~/public_html/lib/tk-tools/bin/tk.php bc -M 2 /home/smb/Misc/_sitebak/tktask tgz

# anat-vet (tkapd)
00 3  * * *  ~/public_html/lib/tk-tools/bin/tk.php sb anat-vet /home/smb/Misc/_sitebak/anat-vet
30 3  * * *  ~/public_html/lib/tk-tools/bin/tk.php bc -M 7 /home/smb/Misc/_sitebak/anat-vet sql.gz
00 3  * * 5  ~/public_html/lib/tk-tools/bin/tk.php sb -D anat-vet /home/smb/Misc/_sitebak/anat-vet
30 3  * * 5  ~/public_html/lib/tk-tools/bin/tk.php bc -M 2 /home/smb/Misc/_sitebak/anat-vet tgz
```