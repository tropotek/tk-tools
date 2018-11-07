TODO
====


 - Update Tag Project command. The sub-libs should be tagged first, then we can   
   run `composer update` and a `git commit` on the project befor tagging it.
   The possability exist to modify all the version in the composer.json if needed.
   this would allow every release to be static (oshould be configurable)

 - Package all tools into a tk.phar file so the `tk` can be the launch command for all build tools
   See this link: [http://moquet.net/blog/distributing-php-cli/][phar_tutorial]
   Also check out composer's compiler code: [\Composer\Compiler class][phar_composer]

 - Once a framework for the phar package is created migrate all commands into the phar package
   ready for execution: EG:
```
$ tk selfupdate
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
```

[phar_tutorial]: http://moquet.net/blog/distributing-php-cli/
[phar_composer]: https://github.com/composer/composer/blob/1.0.0-alpha7/src/Composer/Compiler.php




