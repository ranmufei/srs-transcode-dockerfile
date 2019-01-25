FROM ranmufei/srs:php5-transcode-server

ADD ./runffmpeg.php /var/www/html/
ADD ./docker.conf /srs/conf/
ADD ./starthttp.sh /srs/
#RUN service apache2 start
#WORKDIR /var/www/html/




