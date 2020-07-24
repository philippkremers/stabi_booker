# stabi_booker
This script automatically books selected seats at Staatsbibliothek zu Berlin as soon as they become available.

# Installation
First, unpack the directory on your server. Second, adjust the configuration variables at the beginning of `stabi_booker.php` to ensure that the script signs in the right person. Third, set up a cronjob that executes `stabi_booker.php` on a regular basis (say, in five minute intervals).
