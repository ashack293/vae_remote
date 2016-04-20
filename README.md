# vae_remote

PHP Library that provides VaeML and Vae PHP functions


## Prerequisites

 - VaeQL (see vaeql repository)
 - VaeDB and VaeRubyd (see vae_thrift repository)
 - PHP 7
 - memcached (running on port 11211)
 - MySQL (running)


### Installing other Vae Project prerequisites

Install VaeQL, VaeDB, and VaeRubyd FIRST, before installing Vae Remote.

The Git repositories for these projects is linked to this project
using a Git Submodule.  To install as a submodule, do the following:

    git submodule sync
    git submodule update --init
    git submodule foreach git pull origin mater

Then look in tests/dependencies/vaeql and
tests/dependencies/vae_thrift.

There are README.md files in the respective vaeql and vae_thrift
repositories that should explain what's needed there.

Follow those README.md's to get those projects installed before
moving on.


### Installing Prerequisites on Mac OS X

You should have these from other Vae projects, but if not:

    brew install memcached
    brew install mysql


## Test Suite

Vae Remote includes a large test suite that tests both Vae Remote as
well as VaeQL and VaeDB and VaeRubyd.  This can be found in the tests/
folder.


### Developing VaeML Rendering Tests

Inside the tests/render_tests/ folder, there are several VaeML
test files.  The format of these is:

    input VaeML
    >
    output HTML
    =
    input VaeML
    >
    output HTML
    =
    etc.


Note that the test suite is designed using the old deprecated method of
putting <v:else> tags inside of their container <v:if>, rather than
after them.  We would welcome a commit that rearranges the test suite to
use the new style of putting <v:else> after the <v:if>.


### Running The Suite

First, make sure you have MySQL running locally and have created a
database called 'vaedb' using the instructions in the vae_thrift
repository.

In another window, change dir to where you have VaeDB compiled.  This is
part of the vae_thrift project.

    cd ../vae_thrift/cpp/
    ./vaedb --test

If your local MySQL database is named other than 'vaedb' or the
username/password is different from 'root' and no password, you'll need
to pass additional arguments to vaedb to point at the proper MySQL
server.  Run ./vaedb --help to learn how.

Also start VaeRubyd, which is also part of the vae_thrift project:

    cd ../vae_thrift/rb
    bundle exec ruby vaerubyd.rb

Then you are ready to run the tests:

    php tests/_all.php

