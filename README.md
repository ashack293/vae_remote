# vae_remote

PHP Library that provides VaeML and Vae PHP functions


## Prerequisites

 - VaeQL (see vaeql repository)
 - VaeDB and VaeRubyd (see vae_thrift repository)
 - PHP 5.3
 - memcached (running on port 11211)
 - MySQL (running)


### Installing other Vae Project prerequisites

Install VaeQL, VaeDB, and VaeRubyd FIRST, before installing Vae Remote.

There are README.md files in the respective vaeql and vae_thrift
repositories that should explain what's needed there.


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

In another window, change dir to where you have VaeDB compiled.  This is
part of the vae_thrift project.

    cd ../vae_thrift/cpp/
    ./vaedb --test

Also start VaeRubyd, which is also part of the vae_thrift project:

    cd ../vae_thrift/rb
    ruby vaerubyd.rb

Then you are ready to run the tests:

    php tests/_all.php
