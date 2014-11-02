[TK Lib Command Tools](http://www.tropotek.com) | [Documentation
table of contents](TOC.md)

# Versioning

When releasing virsions of your projects/packages keep the following rules in mind.

## Major version

When the major version number increases, this means the new version is NOT backward
compatible with all previous versions. Most of the time this means you better not
use it in your current project if you are already using RedBeanPHP or you might
have to make some changes to the project to make it work with the new version of the Project.
This is not always as bad as it sounds. For instance version 3 is not backward compatible
with version 2, but only if you use the optimizers (which by default are turned off).
So while this is a major version bump it's actually not that bad. However, while
difference between 2 and 3 is relatively small, the gap between 1 and 2 was a really big one.
Anyway whenever the major version number changes make sure you check the changelog to
determine whether you can upgrade or not.

## Minor version

A minor version change means new features! Minor versions don't break backward compatibiltity,
they just mean new features have been added. Often, this goes hand in hand with changes in
documentation or bugfixes. Therefore it's relatively safe to do a minor upgrade. Be sure
though to check the changelog on the website. You might be able to take advantage of
the new features!

## Point version

A point version or point release happens when the last digit has been increased.
Note that although you might assume a digit normally varies from 0-9, you might
encounter minor and point releases like X.X.12 or X.30.X. Not sure if this will
happen, however as the Project matures you will see less major upgrades and more
minor upgrades and point releases. A point release version is normally a
maintenance version. This may include bugfixes, new tests, documentation
changes or just some code cleanup. While it's always a good idea to scan the
changelog most of the time you can be pretty sure there are no compatibility
issues nor interesting new feature. Of course if you have reported an issue the
point release can be quite interesting because the bug might have been fixed.
In this case, the fix should be mentioned in the changelog.

