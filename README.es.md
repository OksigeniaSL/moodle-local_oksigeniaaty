# Oksigenia Aty (local_oksigeniaaty)

Inicio y diseño propios de cada sitio para la app móvil Oksigenia Aty.

Este plugin de Moodle da a cada sitio alojado su propia pestaña de inicio y su aspecto en la app: un título, una bienvenida y, en versiones posteriores, los accesos, las tarjetas y el color de marca que alimentan el CSS remoto de la app. Se configura desde la administración del propio sitio, así que el diseño de cada sitio cambia sin publicar otra versión de la app. Funciona también en la app oficial de Moodle, y por eso se hace y se prueba antes que la propia app.

Oksigenia Aty es una sola app, publicada por Oksigenia SL, para las plataformas de formación que aloja Oksigenia. La app es un proyecto aparte; este plugin es su parte de Moodle.

## Requisitos

- Moodle 4.5 o posterior (compatibles: 4.5 y 5.3).

## Instalación

Copia el plugin en `local/oksigeniaaty` de tu Moodle y termina la actualización desde *Administración del sitio*, o instala el ZIP desde *Administración del sitio ▸ Extensiones ▸ Instalar extensiones*.

## Ajustes

*Administración del sitio ▸ Extensiones ▸ Plugins locales ▸ Oksigenia Aty*: el color de marca, la pestaña de inicio (título, bienvenida, logo y accesos), el CSS adicional de la app, los enlaces a las tiendas y qué cohortes pueden entrar en la app sin contraseña.

*Administración del sitio ▸ Extensiones ▸ Plugins locales ▸ Oksigenia Aty: QR del centro*: el código QR del sitio con su color de marca y su logo, para descargarlo en SVG y usarlo en carteles y circulares. Además, cada usuario tiene su propio enlace y QR de un solo uso en *Abrir en la app Aty*.

## Privacidad

El plugin guarda un hash del PIN de la app de cada usuario (nunca el PIN), con sus intentos fallidos, y usa las claves de un solo uso de Moodle para los enlaces de entrada. Los dos están declarados en la API de privacidad de Moodle, que los exporta y los borra con el resto de los datos del usuario.

## Licencia

GNU GPL v3 o posterior. Véase [LICENSE](LICENSE).

Copyright © Oksigenia SL.
