FROM nginx:alpine
ENV CRYPTOGRAPHY_DONT_BUILD_RUST=1
RUN apk add python3 python3-dev py3-pip build-base libressl-dev musl-dev libffi-dev
RUN apk add certbot-nginx
RUN mkdir /etc/letsencrypt
RUN cp  /usr/share/zoneinfo/America/Boa_Vista /etc/localtime
RUN echo "America/Boa_Vista" > /etc/timezone
RUN rm /etc/nginx/conf.d/default.conf
