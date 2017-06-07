# BeSimpleSoap

Build SOAP and WSDL based web services.
This fork is a refactored version that fixes a lot of errors and provides
better API, robust, stable and modern codebase.
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

**Untouched!**
The component is not affected by refactoring so it should work properly. 
For further information see the original [README](https://github.com/BeSimple/BeSimpleSoap/blob/master/src/BeSimple/SoapWsdl/README.md).

## BeSimpleSoapBundle

**Unsupported!**
The BeSimpleSoapBundle is a Symfony2 bundle to build WSDL and SOAP based web services.
For further information see the the original [README](https://github.com/BeSimple/BeSimpleSoap/blob/master/src/BeSimple/SoapBundle/README.md).
*Will not work since the Symfony libraries were removed and usages of other components were not refactored. Feel free to fork this repository and fix it!* 

# Installation

If you do not yet have composer, install it like this:

```sh
curl -s http://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin
```

Create a `composer.json` file:

```json
{
    "require": {
        "tuscanicz/soap": "^4.2"
    }
}
```

Now you are ready to install the library:

```sh
php /usr/local/bin/composer.phar install
```

# How to use

You can investigate the unit tests dir ``tests`` in order to get a clue.
Forget about associative arrays, vague configurations, multiple extension and silent errors! 
This may look a bit more complex at the first sight, 
but it will guide you to configure and set up your client or server properly.

## Example of soap client call

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
try {
    $soapResponse = $soapClient->soapCall('myMethod', [$myRequest]);
} catch (SoapFaultWithTracingData $fault) {
    var_dump($fault->getSoapResponseTracingData()->getLastRequest());
}
```
In this example, a ``MyRequest`` object has been used to describe request.
Using a ClassMap, you help SoapClient to turn it into XML request.

## Example of soap server handling

Starting a SOAP server is a bit more complex.
I recommend you to inspect SoapServer unit tests for inspiration. 

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

In the service you should describe SOAP methods from given WSDL.
In the example, the dummyServiceMethod is called.
The method will receive request object and return response object that are matched according to the class map.

See a simplified implementation of ``dummyServiceMethod`` to get a clue:

```
    /**
     * @param DummyServiceRequest $dummyServiceRequest
     * @return DummyServiceResponse
     */
    public function dummyServiceMethod(DummyServiceRequest $dummyServiceRequest)
    {
        $response = new DummyServiceResponse();
        $response->status = true;

        return $response;
    }
```

For further information and getting inspiration for your implementation, see the unit tests in ``tests`` dir. 

# Contribute

[![Build Status](https://travis-ci.org/tuscanicz/BeSimpleSoap.svg?branch=master)](https://travis-ci.org/tuscanicz/BeSimpleSoap)

Feel free to contribute! Please, run the tests via Phing ``php phing -f build.xml``.

**Warning:** Unit tests may fail under Windows OS, tested under Linux, MacOS.
