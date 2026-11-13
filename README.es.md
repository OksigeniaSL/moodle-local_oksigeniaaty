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

*Los H5P se juegan en la app* (activado de serie): permite la descarga de todos los H5P que tienen oculta la barra de acciones, para que la app los juegue en el teléfono, también sin conexión y en iPhone. Sin la barra, en la web no aparece ningún botón de descarga.

## Para otros plugins

Un plugin puede estar en el inicio de la app, en la oficial de Moodle y en Oksigenia Aty, con funciones en su `lib.php`:

- `<component>_oksigeniaaty_home_cards(int $userid): array` añade para ese usuario una sección de filas y un mosaico, que
  abren una página del plugin en la app o una dirección. Los campos se explican en `classes/local/homecards.php`. Un
  plugin que el sitio apaga en la app (*Administración del sitio ▸ Aplicación móvil ▸ Características móviles*) tampoco
  enseña sus tarjetas.
- `<component>_oksigeniaaty_browser_pages(): array` enumera las páginas del plugin que funcionan mejor en el navegador
  del teléfono que dentro de la app (compartir, guardar un fichero hecho en la página, un fichero de calendario, un vídeo
  largo): rutas desde la raíz del sitio, una página o una carpeta acabada en `/`. En ellas no sale la barra de «Abrir en
  la app», y las filas, los mosaicos y los accesos del inicio que llevan a ellas las abren en el navegador del teléfono.
- `<component>_oksigeniaaty_user_brand(int $userid): ?array` le da a un usuario su propio nombre, color, logo o imagen, y lema,
  donde varios colegios o bibliotecas comparten un sitio.

Y `\local_oksigeniaaty\api::issue_link($userid, $days)` y `api::send_link($userid, $days)` crean y envían un enlace de
entrada a la app, de un solo uso, para un plugin que da de alta a gente que aún no tiene contraseña.

Con Oksigenia Tembiapo («Tu trabajo» en el área personal), quien puede mandar la app tiene una puerta a ella con cuántas
personas la tienen, y quien administra el sitio, el QR del centro a mano.

## Privacidad

El plugin guarda un hash del PIN de la app de cada usuario (nunca el PIN), con sus intentos fallidos, y usa las claves de un solo uso de Moodle para los enlaces de entrada. Los dos están declarados en la API de privacidad de Moodle, que los exporta y los borra con el resto de los datos del usuario.

## Licencia

GNU GPL v3 o posterior. Véase [LICENSE](LICENSE).

Copyright © Oksigenia SL.
