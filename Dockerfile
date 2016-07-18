FROM activatedgeek/nginx-php:latest

MAINTAINER Sanyam Kapoor "1sanyamkapoor@gmail.com"

RUN apk add --update python py-pip &&\
  pip install PyMySQL==0.7.2 munkres==1.0.7 &&\
  # composer install &&\
  rm -rf /var/cache/apk/*

# Add source code and configs
ADD ./config/site.conf /etc/nginx/sites-available/default
ADD ./config/nginx.conf /etc/nginx/nginx.conf

EXPOSE 80

WORKDIR /app

CMD ["/bin/bash", "/docker-entrypoint.sh"]
