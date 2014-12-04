
GIT NOTES
=========


Tags:
----------
    $ git tag
    
Create a tag:

    $ git tag -a '1.4.0' -m 'my version 1.4.0'
    


Branches:
---------

Branch list:

    $ git branch


Branch create :

    $ git checkout -b iss53

This is shorthand for:

    $ git branch iss53  
    $ git checkout iss53


Diff
----

    $ git diff --name-status 1.2.0 master

output:

    M       bin/tkTagProject.php
    M       changelog.md
    M       composer.json


Log
---

    $ git log --name-status --oneline 1.2.0 master
    
output

    a9116bf Fixed --tagdeps option in tkTagProject
    M       bin/tkTagProject.php
    88e540d Fixed --tagdeps option in tkTagProject
    M       bin/tkTagProject.php
    e26acdc Updated the changelog for version 1.2.0, another thing to fix...
    M       changelog.md
    ad751bc Auto Commit
    M       composer.json
    4c2e49b Auto Commit
    M       composer.json
    3d5cf09 updated todo.md and preparing for historic release before updating for phar packaging
    M       .gitignore
    M       Tbx/Vcs/Adapter/Git.php
    M       bin/tkTagProject.php
    A       todo.md
    66d5035 -
    M       Tbx/Vcs/Adapter/Git.php
    c8b577c updated tag release system for git
    M       Tbx/Vcs/Adapter/Git.php
    M       Tbx/Vcs/Adapter/Iface.php
    M       Tbx/Vcs/Adapter/Svn.php
    M       bin/tkTag.php
    M       bin/tkTagProject.php


Remotes
-------

    $ git remote -v

output:

    origin  https://github.com/tropotek/tk-tools.git (fetch)
    origin  https://github.com/tropotek/tk-tools.git (push)





