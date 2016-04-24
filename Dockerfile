FROM activatedgeek/nginx-php:latest

MAINTAINER Sanyam Kapoor "1sanyamkapoor@gmail.com"

RUN apt-get -y update &&\
  apt-get install -y python-pip &&\
  pip install PyMySQL==0.7.2 munkres==1.0.7 &&\
  composer install &&\
  apt-get autoremove -y &&\
  apt-get clean &&\
  apt-get autoclean &&\
  rm -rf /var/lib/apt/lists/* &&\
  rm -rf /usr/share/man/*

# Add source code and configs
ADD ./config/site.conf /etc/nginx/sites-available/default
ADD ./config/nginx.conf /etc/nginx/nginx.conf

EXPOSE 80

WORKDIR /app

CMD ["/bin/bash", "/docker-entrypoint.sh"]
