FROM php:8.x-apache

# ... tempatkan script COPY atau instalasi extension Anda di sini ...

# HAPUS MODUL YANG BENTROK SAAT BUILD TIME
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load \
    && rm -f /etc/apache2/mods-enabled/mpm_event.conf \
    || true

# Nyalakan kembali modul mpm_prefork yang aman untuk PHP
RUN ln -s /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load \
    && ln -s /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf \
    || true

EXPOSE 80