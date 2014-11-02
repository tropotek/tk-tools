Tropotek Toolbox
================

Published: 01 Jan 2014

Authors:

  * [Michael Mifsud][tropotek]


Requirements
============

 - PHP5+ (CLI)



Installation
============

 1. Place the source files somewhere on your server `/usr/local/src` for example.
 2. Run `composer.phar install`
 3. cd into the bin directory and run `sudo ./install-cmd` to install for all users.
 4. Optionally if you only want to install for a user do not use sudo and the commands will
    be installed into the user bin directory.



Commands
========

 - **propset** Deprecated: kept for compatibility.
 - **phplog** Tail a log file in the users path: /home/{username}/log/error.log
 - **commit** A recursive `svn commit` command that searches for external packages within a project.
 - **update** A recursive `svn update` command that searches for external packages within a project.
 - **merge** A recursive `svn merge` command that searches for external packages within a project.
 - **tkPassGen** Used to create temporary authentication hash's for external sites.
 - **tkStrreplace** A simple string replace command.
 - **tkTag** Used to tag a repository, updates composer.json and changelog.md files.
 - **tkTagProject** Recursively tags a Project's repository and its dependant packages, updates composer.json and changelog.md files.
 - **tkToUtf8** Update files to the UTF-8 encoding.
 - **tkVidHtml5Cnv** Convert video file to a number of formats for web viewing using HTML5 standards.


TODO
====

 - Create a new db command to backup all DB's on a server to individual .sql files. (Have it somewhere already, find it and)
   put it here.








[tropotek]: http://www.tropotek.com.au/