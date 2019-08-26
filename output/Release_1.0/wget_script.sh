#!/bin/bash
# wget_script.sh 	
# This program downloads files of the last 4 days
# require: lwp-request
# attention: the --date or -d option is only available in GNU date.
# @author  Marco Recke, recke@gbv.de
# @version 1.05-14.03.2013

# constant, please adjust before start
user="xxx"
pass="xxxx"
server="oase.gbv.de"
account="xxxx"
format="json"
folder="/home/something"

for i in {2..4} ; do
  x=$(date --date "$i days ago" +%Y-%m-%d)
  month=$(date --date "$i days ago" +%m)
  year=$(date --date "$i days ago" +%Y)
  file=$x"_"$x"."$format


  url="https://"$user":"$pass"@"$server"/"$account"/"$year"/"$month"/"
  urlfile=$url$file
 
  # testing if new file really available 
  Test=`lwp-request -ds $urlfile  2>&1 | cat`
  
  set -- $Test
  num=$1
  shift
  
  if [ $num = "200" ] ; then
  # OK, file exists on server 

  if [ -f $file ]
   then
    # file already downloaded, do nothing 
    a=1
   else
    # download,suppress the output 
     wget --no-check-certificate  $urlfile > /dev/null 2>&1 
  fi

else

# file does not exist on server
  echo "file does not exist on server: $file" > error.txt
fi

done
