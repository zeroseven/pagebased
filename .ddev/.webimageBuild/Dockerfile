
#ddev-generated - Do not modify this file; your modifications will be overwritten.
ARG BASE_IMAGE="scratch"

### DDEV-injected base Dockerfile contents
FROM $BASE_IMAGE
SHELL ["/bin/bash", "-c"]

ARG TARGETPLATFORM
ARG TARGETARCH
ARG TARGETOS
ARG username
ARG uid
ARG gid
ARG DDEV_PHP_VERSION
ARG DDEV_DATABASE
RUN getent group tty || groupadd tty
RUN (groupadd --gid "$gid" "$username" || groupadd "$username" || true) && \
    (useradd -G tty -l -m -s "/bin/bash" --gid "$username" --comment '' --uid "$uid" "$username" || \
    useradd -G tty -l -m -s "/bin/bash" --gid "$username" --comment '' "$username" || \
    useradd -G tty -l -m -s "/bin/bash" --gid "$gid" --comment '' "$username" || \
    useradd -G tty -l -m -s "/bin/bash" --comment '' "$username")

### DDEV-injected addition of not-preinstalled PHP version
RUN /usr/local/bin/install_php_extensions.sh "php8.1" "${TARGETARCH}"

### DDEV-injected extra content

RUN mkdir -p /home/$username && chown $username /home/$username && chmod 600 /home/$username/.pgpass
ENV N_PREFIX=/home/$username/.n
ENV N_INSTALL_VERSION="20"

RUN log-stderr.sh mariadb-client-install.sh || true

RUN log-stderr.sh mariadb-compat-install.sh || true

RUN log-stderr.sh mariadb-skip-ssl-wrapper-install.sh || true


### DDEV-injected from webimage_extra_packages or dbimage_extra_packages
RUN (timeout 120 apt-get update || true) && DEBIAN_FRONTEND=noninteractive apt-get install -y -o Dpkg::Options::="--force-confold" --no-install-recommends --no-install-suggests php8.1-pcov

### DDEV-injected composer update
RUN export XDEBUG_MODE=off; composer self-update --stable || composer self-update --stable || true; composer self-update --2 || log-stderr.sh composer self-update --2 || true

### DDEV-injected php default version setting
RUN update-alternatives --set php /usr/bin/php8.1
RUN chmod ugo+rw /var/log/php-fpm.log && chmod ugo+rwx /var/run && ln -sf /usr/sbin/php-fpm8.1 /usr/sbin/php-fpm
RUN mkdir -p /tmp/xhprof
RUN chmod -fR ugo+w /etc/php /var/lib/php/modules /tmp/xhprof
RUN phpdismod blackfire xdebug xhprof

### DDEV-injected folders permission fix
RUN chmod 777 /run/php /var/log && \
    chmod -f ugo+rwx /usr/local/bin /usr/local/bin/* && \
    mkdir -p /tmp/xhprof && chmod -R ugo+w /etc/php /var/lib/php /tmp/xhprof
