# BeSimpleSoap

Build SOAP and WSDL based web services.
This fork from 2017 is a refactored version that fixes a lot of errors and provides
better APi, more robust, stable and modern codebase.
See [How to use](#how-to-use) that will help you to understand the magic.

# Components

BeSimpleSoap consists of five components ...

## BeSimpleSoapClient

**Refactored** BeSimpleSoapClient is a component that extends the native PHP SoapClient with further features like SwA and WS-Security.

## BeSimpleSoapServer

**Refactored** BeSimpleSoapServer is a component that extends the native PHP SoapServer with further features like SwA and WS-Security.

## BeSimpleSoapCommon

**Refactored** BeSimpleSoapCommon component contains functionality shared by both the server and client implementations.

## BeSimpleSoapWsdl

Currently **unsupported!** For further information see the [README](https://github.com/BeSimple/BeSimpleSoap/blob/master/src/BeSimple/SoapWsdl/README.md).

## BeSimpleSoapBundle

Currently **unsupported!**
The BeSimpleSoapBundle is a Symfony2 bundle to build WSDL and SOAP based web services.
For further information see the the original [README](https://github.com/BeSimple/BeSimpleSoap/blob/master/src/BeSimple/SoapBundle/README.md).
*May not work properly since the Symfony libraries were removed.* 

# Installation

If you do not yet have composer, install it like this:

```sh
curl -s http://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin
```

Create a `composer.json` file:

```json
{
    "require": {
        "besimple/soap": "0.2.*@dev"
    }
}
```

Now you are ready to install the library:

```sh
php /usr/local/bin/composer.phar install
```

# How to use

You can investigate the unit tests dir ``tests`` in order to get a clue.
Forget about associative arrays, multiple extension and silent errors! 

## Small example of soap client call

```php
$soapClientBuilder = new SoapClientBuilder();
$soapClient = $soapClientBuilder->build(
    SoapClientOptionsBuilder::createWithDefaults(),
    SoapOptionsBuilder::createWithDefaults('http://path/to/wsdlfile.wsdl')
);
$myRequest = new MyRequest();
$myRequest->attribute = 'string value';
$soapResponse = $soapClient->soapCall('myMethod', [$myRequest]);

var_dump($soapResponse); // Contains Response, Attachments
```

### Something wrong?!
Turn on the tracking and catch `SoapFaultWithTracingData` exception to get some sweets :) 

```php
$myRequest = new MyRequest();
$myRequest->attribute = 'string value';

try {
    $soapResponse = $soapClient->soapCall('MyMethod', [$myRequest]);
} catch (SoapFaultWithTracingData $fault) {
    var_dump($fault->getSoapResponseTracingData()->getLastRequest());
}
```
In this example, a ``MyRequest`` object has been used to describe request.
Using a ClassMap, you help SoapClient to turn it into XML request.

## Small example of soap server handling

Starting a SOAP server is a bit more complex.
I would suggest you to inspect SoapServer unit tests to get complete image. 
But don't be scared too much, you just need to create a DummyService that will
handle your client SOAP calls.

```php
$dummyService = new DummyService();
$classMap = new ClassMap();
foreach ($dummyService->getClassMap() as $type => $className) {
    $classMap->add($type, $className);
}
$soapServerBuilder = new SoapServerBuilder();
$soapServerOptions = SoapServerOptionsBuilder::createWithDefaults($dummyService);
$soapOptions = SoapOptionsBuilder::createWithClassMap($dummyService->getWsdlPath(), $classMap);
$soapServer = $soapServerBuilder->build($soapServerOptions, $soapOptions);

$request = $soapServer->createRequest(
    $dummyService->getEndpoint(),
    'DummyService.dummyServiceMethod',
    'text/xml;charset=UTF-8',
    '<received><soap><request><here /></request></soap></received>'
);
$response = $soapServer->handleRequest($request);

var_dump($response); // Contains Response, Attachments
```
In this example, a ``DummyService`` service has been used to handle request.
Using a service can help you create coherent SoapServer endpoints.
Service can hold an endpoint URL, WSDL path and a class map as associative array.
You can hold a class map as ``ClassMap`` object directly in the ``DummyService`` instead of array.