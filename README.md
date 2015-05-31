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


### Create Local MySQL Database for Vae Remote

Create a local mysql database called vaedb.  Then create a user
called vaedb and give that user a password.  

TODO: users are currently assumed to actually be called verbshared and
db called av_verbshared.  This will change.

TODO: MOVE THE USERNAME/PASSWORD TO A CONFIG VARIABLE.

Import the schema as follows:

    mysql -uvaedb < db/schema.sql

TODO: Actually make the above file exist.  Currently the file is
MISSING, please generate one from production.


## How to run the Test Suite

In another window, change dir to where you have VaeDB compiled.  This is
part of the vae_thrift project.

    cd ../vae_thrift/cpp/
    ./vaedb --test

Also start VaeRubyd, which is also part of the vae_thrift project:

    cd ../vae_thrift/ruby
    ruby vaerubyd.rb

Then you are ready to run the tests:

    php tests/_all.php
