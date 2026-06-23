#!/bin/bash

DIR="$(cd "$(dirname "$0")" && pwd)"
"$DIR/../../../../vendor/bin/crunz" schedule:run "$DIR" 
