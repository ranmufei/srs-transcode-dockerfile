FROM ranmufei/srs:php5-transcode-server

# 重新安装ffmpeg 4.1
ARG FFMPEG_URL=http://www.linksame.com/img/ffmpeg-4.1.tar.gz
#https://johnvansickle.com/ffmpeg/releases/ffmpeg-$FFMPEG_VERSION-64bit-static.tar.xz

ADD ${FFMPEG_URL} /tmp/ffmpeg.tar.gz
RUN cd /tmp && tar xJf ffmpeg.tar.gz

#FROM scratch

run cp /tmp/ffmpeg*/ffmpeg /bin/


ADD ./runffmpeg.php /var/www/html/
ADD ./docker.conf /srs/conf/
ADD ./starthttp.sh /srs/
#RUN service apache2 start
#WORKDIR /var/www/html/

ADD ./rc.local /etc/
# /bin/bash,-c,/etc/rc.local; /bin/bash
ENTRYPOINT ["/bin/bash", "-c", "/etc/rc.local; /bin/bash"]

