# Citation Type Selector

Allows users to specify citation type based on genre of Islandora object.

## Introduction

This module extends the Discovery Garden Islandora Citation module allowing for Citation types as well as styles.

### Citation Type Field
This populates the requires 'field_csl_type' with a value mapped and pulled from a Genre field.

## Installation

Install as
[usual](https://www.drupal.org/docs/extending-drupal/installing-modules).

## Configuration

THe mapping form found at /admin/config/system/citation-select-settings allows admins to select a Content Classification Field,
the vocabulary that field draws from, the Citation Type Field, and the Vocabulary that field draws from.
These would normally be the Genre field using the Genre vocabulary, and the Object Type (Citation) field and the CSL Type vocabulary

## Usage

After installing and enabling module, configure at /admin/config/system/citation-select-settings.


## Maintainers
Current maintainers:

* [Robertson Library](https://library.upei.ca/)

## License
[GPLv3](http://www.gnu.org/licenses/gpl-3.0.txt)
