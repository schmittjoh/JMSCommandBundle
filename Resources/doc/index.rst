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

This command will automatically add bundle exceptions for all SPL exceptions plus
the approriate use statements according to Symfony2's best practices.