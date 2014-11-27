TODO
====

 - Package all tools into a tk.phar file so the `tk` can be the launch command for all build tools
   See this link: [http://moquet.net/blog/distributing-php-cli/][phar_tutorial]
   Also check out composer's compiler code: [\Composer\Compiler class][phar_composer]
 - Once a framework for the phar package is created migrate all commands into the phar package
   ready for execution: EG:
<pre><code>$ tk selfupdate
$ tk ci                                // commit project (in WD version)  
$ tk tail --file=/path/logFile.log     // tklog  
$ tk hash --type=md5                   // tkMd5  
$ tk back-door --timezone=[zone]       // tkPassGen (no doc)  
$ tk st                                // status command for project  
$ tk replace [find] [replace]          // tkStrreplace  
$ tk tag                               // tkTag  
$ tk ptag --depTag                     // tkTagProject  
$ tk utf8 [srcFile] [dstFile]          // tkToUtf8  
$ tk up                                // update project (in WD version)  
$ tk help [cmd]                        // basic help text for package  
</code></pre>

 - Update git and SVN commands to work in a working directory only no remote commands
 - Create a new db command to backup all DB's on a server to individual .sql files. 
   (Have it somewhere already, find it and put it here.)



[phar_tutorial]: http://moquet.net/blog/distributing-php-cli/
[phar_composer]: https://github.com/composer/composer/blob/1.0.0-alpha7/src/Composer/Compiler.php