#!/bin/bash

sudo adduser $USER www-data
#sudo chown -R www-data:www-data /home/$USER/public_html
#sudo chown -R $USER:www-data /home/$USER/public_html
sudo chgrp -R www-data /home/$USER/public_html
sudo chmod -R ug+rw /home/$USER/public_html


