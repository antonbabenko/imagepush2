[Imagepush.to](https://imagepush.to/) web-site source code released 15.2.2014.

Author and maintainer: [Anton Babenko](http://github.com/antonbabenko)

[![Build Status](https://travis-ci.org/antonbabenko/imagepush2.png?branch=master)](https://travis-ci.org/antonbabenko/imagepush2) [![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/antonbabenko/imagepush2/badges/quality-score.png?s=c1491d13878f7807fbed2bc0856bb84d91f1d5af)](https://scrutinizer-ci.com/g/antonbabenko/imagepush2/) [![Code Coverage](https://scrutinizer-ci.com/g/antonbabenko/imagepush2/badges/coverage.png?s=141654b4594727048c5d0a4cf7c6064126afc136)](https://scrutinizer-ci.com/g/antonbabenko/imagepush2/)

## How to deploy it?

This site is running on AWS using Elastic Beanstalk in eu-west-1.

Typical command to deploy new code is:

```$ eb deploy prod7-eb```

Before running it you need to create AWS infrastructure. Check [terraform](./terraform) directory for code.

## License

[Apache License 2.0](http://www.apache.org/licenses/LICENSE-2.0)
