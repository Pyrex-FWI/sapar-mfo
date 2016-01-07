

# sapar-organizer Package

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.txt)
[![Build Status](https://travis-ci.org/Pyrex-FWI/sapar-organizer.svg?branch=master)](https://travis-ci.org/Pyrex-FWI/sapar-organizer)


## Tests

Make sure you have mediainfo available at location /usr/bin/mediainfo.

### Usage


```sh
$ src/bin/organize organize /output/directory /path/file.mp3
```

```sh
$ src/bin/organize organize /output/directory /path/file.mp3
```
```sh
$ src/bin/organize organize /output/directory --file-dir /path/of/files/to/organize --move-untagged-to /path/to/move/incorrect-tagged-files
```

```sh
$ find /path/of/files/to/organize -type f -name '*.mp3' -exec src/bin/organize organize /output/directory {} --move-untagged-to /path/to/move/incorrect-tagged-files  \;
```