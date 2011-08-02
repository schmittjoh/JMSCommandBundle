========
Overview
========

This bundle provides useful commands that ease development.


Installation
------------
Checkout a copy of the code::

    git submodule add https://github.com/schmittjoh/CommandBundle.git src/JMS/CommandBundle
    
Then register the bundle with your kernel::

    // in AppKernel::registerBundles()
    $bundles = array(
        // ...
        new JMS\CommandBundle\JMSCommandBundle(),
        // ...
    );

Commands
--------

exceptionify
~~~~~~~~~~~~

Usage: exceptionify <bundlename>

::

    php app/console exceptionify MyBundleName

This command will automatically add bundle exceptions for all SPL exceptions that are
used inside the bundle plus the approriate use statements according to Symfony2's best practices.

licensify
~~~~~~~~~

Usage: licensify <bundlename>

::

    php app/console licensify MyBundleName --license Apache2
    
    
This command will automatically add the given license to all PHP files. You can
change the license text (for example to add your name) by overwriting this bundle
in your application.

generate:test-application
~~~~~~~~~~~~~~~~~~~~~~~~~

Usage: generate:test-application <bundlename>

::

    php app/console generate:test-application JMSCommandBundle
    
Generates a test application inside a bundle which you can use for functional tests.