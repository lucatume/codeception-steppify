# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased][unreleased]

##[1.0.6] 2020-06-05

### Added

- namespace support (thanks @patrickvale)

##[1.0.5] 2020-06-04

### Fixed
- documentation (thanks @pixelbrackets)
- compatiblity with Codeception >= 2.2 (thanks @patrickvale)

##[1.0.4] 2017-10-04

### Fixed
- generation methods issues

##[1.0.3] 2016-12-12

### Added
- feedback line when using configuration file

### Fixed
- issue where not using a `\Codeception\Module\<Module>` fully qualified in config file would cause the module configuration to be ignored.

##[1.0.2] 2016-12-09

### Fixed
- avoid argument conversion when there are no array arguments in the method signature

##[1.0.1] 2016-12-08

### Fixed
- missing support for `on` stopword
- force rewrite on steps file by default
- move function to convert table nodes to arrays to `src/functions.php` file
- step definition generation for better carry over and parameter support

## 1.0.0 - 2016-12-07

### Added
- this changelog
- initial commit

[1.0.1]: https://github.com/lucatume/codeception-steppify/compare/1.0.0...1.0.1
[1.0.2]: https://github.com/lucatume/codeception-steppify/compare/1.0.1...1.0.2
[1.0.3]: https://github.com/lucatume/codeception-steppify/compare/1.0.2...1.0.3
[1.0.4]: https://github.com/lucatume/codeception-steppify/compare/1.0.3...1.0.4
[1.0.5]: https://github.com/lucatume/codeception-steppify/compare/1.0.4...1.0.5
[1.0.6]: https://github.com/lucatume/codeception-steppify/compare/1.0.5...1.0.6
[unreleased]: https://github.com/lucatume/codeception-steppify/compare/1.0.6...HEAD

