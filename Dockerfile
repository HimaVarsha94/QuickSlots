FROM activatedgeek/nginx-php:latest

MAINTAINER StoryXpress API <Sanyam Kapoor "sanyam@storyxpress.co">

# Add source code and configs
ADD ./config/site.conf /etc/nginx/sites-available/default
ADD ./config/nginx.conf /etc/nginx/nginx.conf

EXPOSE 80

WORKDIR /app

CMD ["/bin/bash", "/docker-entrypoint.sh"]
