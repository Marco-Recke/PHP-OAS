#!/bin/bash
# @file                 sort.sh
# @author               recke@gbv.de
# @date                 14-07-2014
# @version              1.3
# @brief                sucht mittels find nach json dateien , zerlehgt den Pfad und kopiert diese in ein neues Verzeichnis
# @brief                zb. /var/www/modularoutput_oneSP/reports/4/oaiwww.pedocs.de-opus/2014/07/2014-07-12_2014-07-12.json


basicpath="/var/www/"
datum=$(date +"%Y-%m-%d")

# Dataprovider
# 1 Econstor
# 2 Kiel
# 4 Pedocs
# 5 heidi
# 6 Saarland

#  Parsing strings:
 find /var/www/modularoutput_oneSP -type f -name "*.json"  | while read file
 do
  dataprovider=`  echo $file | awk '{split($0,array,"/")} END {print array[6]}'`
# echo "Dataprovider:" $dataprovider

  identifier=`  echo $file | awk '{split($0,array,"/")} END {print array[7]}'`
# echo "identifier:" $identifier

  Jahr=`  echo $file | awk '{split($0,array,"/")} END {print array[8]}'`
# echo "Jahr:" $Jahr

  Monat=`  echo $file | awk '{split($0,array,"/")} END {print array[9]}'`
# echo "Monat:" $Monat

  Dateiname=`  echo $file | awk '{split($0,array,"/")} END {print array[10]}'`
# echo "Dateiname:"$Dateiname


# erste Runde, trennt nach Dataprovider

    case $dataprovider in

         "1")
           newpath=$basicpath"econstor/"$Jahr"/"$Monat
         ;;
         "2")
           newpath=$basicpath"kiel/test/"$Jahr"/"$Monat
          ;;
         "4")
           newpath=$basicpath"pedocs/test/"$Jahr"/"$Monat
          ;;
         "5")
           newpath=$basicpath"heidi/test/"$identifier"/"$Jahr"/"$Monat
          ;;
         "6")
          id=${identifier:3}
          newpath=$basicpath"oas_saar/test/"$id"/"$Jahr"/"$Monat
         ;;
          *)
          echo "Error"
         ,,
      esac 
 
# Wenn Dataprovider in der Liste dann kopiere die Datei um

    if [ -d $newpath ]
      then
         mv  $file $newpath
         echo '['$datum']' 'mv' $file  >> /var/www/modularoutput_oneSP/controlmv.log
         echo  '['$datum']'' ->' $newpath >> /var/www/modularoutput_oneSP/controlmv.log
    
      else
          echo '['$datum']' 'Verzeichnis' $newpath 'wird neu angelegt.' >> /var/www/modularoutput_oneSP/controlmv.log
          mkdir -p $newpath
      fi

done

