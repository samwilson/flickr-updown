PhpFlickr CLI
=============

This is a command-line interface (CLI) to Flickr, written in PHP.

[![Build status](https://travis-ci.org/samwilson/phpflickr-cli.svg)](https://travis-ci.org/samwilson/phpflickr-cli)

Features:

* Internationalization.
* Add checksum machine tags (MD5 or SHA1).

## Installation

Get the code and install dependencies:

    git clone https://github.com/samwilson/phpflickr-cli
    cd phpflickr-cli
    composer install

Run the app:

    phpflickr-cli --help

## Authorization

Just run `phpflickr-cli auth` and follow the prompts.
This will create a `config.yml` file containing your access codes; keep it safe.

## Checksums

Add MD5 or SHA1 checksum machine tags to images:

    phpflickr-cli checksums --help

Requires authorization.

## Kudos

* Thanks to inspiration from [TheFox's flickr-cli](https://github.com/TheFox/flickr-cli).

## License

`GPL-3.0-or-later`

Copyright 2018 Sam Wilson. See [LICENSE.txt](LICENSE.txt) for details.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see https://www.gnu.org/licenses/
