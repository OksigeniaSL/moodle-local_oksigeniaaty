<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Cadenas de local_oksigeniaaty, idioma 'es'.
 *
 * @package    local_oksigeniaaty
 * @copyright  2026 Oksigenia SL <dev@oksigenia.cc>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['expiredkey'] = 'Este enlace de acceso ha caducado. Pide uno nuevo.';
$string['forgotpin:emailbody'] = 'Hola {$a->name}:

Usa este enlace para reiniciar tu PIN de Aty y poner uno nuevo. Funciona una sola vez y caduca en 30 minutos:

{$a->link}

Si no lo has pedido tú, puedes ignorar este mensaje.';
$string['forgotpin:emailsubject'] = 'Reinicia tu PIN de Aty';
$string['forgotpin:heading'] = '¿Olvidaste tu PIN de Aty?';
$string['forgotpin:intro'] = 'Escribe tu nombre de usuario o tu correo y te enviaremos un enlace para reiniciar el PIN.';
$string['forgotpin:link'] = '¿Olvidaste el PIN?';
$string['forgotpin:search'] = 'Nombre de usuario o correo';
$string['forgotpin:sent'] = 'Si esa cuenta existe y usa Aty, te hemos enviado por correo un enlace para reiniciar el PIN. Funciona una sola vez y caduca en 30 minutos.';
$string['forgotpin:submit'] = 'Enviar enlace de reinicio';
$string['home:greeting'] = 'Hola, {$a}';
$string['home:mycourses'] = 'Mis cursos';
$string['home:opensoutside'] = 'Se abre fuera de la app';
$string['home:shortcuts'] = 'Accesos';
$string['invalidkey'] = 'Este enlace de acceso no es válido.';
$string['mobilenotenabled'] = 'El acceso desde el móvil no está activado en este sitio.';
$string['mylink:expires'] = 'Este enlace funciona una sola vez y caduca en 30 minutos. Recarga esta página para obtener uno nuevo.';
$string['mylink:heading'] = 'Abrir en la app Aty';
$string['mylink:intro'] = 'Pulsa el botón en tu móvil para abrir la app Aty con la sesión ya iniciada. No hace falta contraseña.';
$string['mylink:nav'] = 'Abrir en la app Aty';
$string['mylink:qralt'] = 'Código QR para entrar en la app Aty';
$string['mylink:qrhint'] = 'O escanea este código con la app Aty en tu móvil.';
$string['oksigeniaaty:issuelink'] = 'Emitir un enlace o QR de acceso a Aty para otro usuario';
$string['open:android'] = 'Disponible en Google Play';
$string['open:expired'] = 'Este enlace de acceso ha caducado o ya se ha usado. Pide uno nuevo a tu centro u organización.';
$string['open:ios'] = 'Descárgala en el App Store';
$string['open:noapp'] = '¿Todavía no tienes la app?';
$string['open:openbtn'] = 'Abrir en Aty';
$string['open:opening'] = 'Abriendo la app...';
$string['open:title'] = 'Entrar en Aty';
$string['pinbiometric'] = 'Confirma tu identidad';
$string['pinbiometricask'] = '¿Usar tu huella o cara para entrar la próxima vez?';
$string['pincancel'] = 'Cancelar';
$string['pininvalid'] = 'El PIN debe tener de 4 a 8 cifras.';
$string['pinlocked'] = 'Demasiados PIN incorrectos. Espera unos minutos y vuelve a intentarlo, o pide un enlace de acceso nuevo.';
$string['pinokenter'] = 'Entrar';
$string['pinoksetup'] = 'Crear PIN';
$string['pinsetup'] = 'Pon un PIN de 4 a 8 cifras para entrar más rápido la próxima vez. Puedes omitirlo y ponerlo más tarde.';
$string['pinskip'] = 'Omitir';
$string['pintitleenter'] = 'Escribe tu PIN';
$string['pintitlesetup'] = 'Crea tu PIN';
$string['pinwrong'] = 'Ese PIN no es correcto.';
$string['pluginname'] = 'Oksigenia Aty';
$string['privacy:metadata:loginkey'] = 'Las claves de un solo uso emitidas para entrar en la app sin contraseña.';
$string['privacy:metadata:loginkey:timecreated'] = 'Cuándo se creó la clave de acceso.';
$string['privacy:metadata:loginkey:userid'] = 'El usuario al que pertenece la clave de acceso.';
$string['privacy:metadata:loginkey:validuntil'] = 'Cuándo caduca la clave de acceso.';
$string['privacy:metadata:pin'] = 'El PIN de acceso que protege la entrada sin contraseña a la app.';
$string['privacy:metadata:pin:fails'] = 'El número de PIN incorrectos seguidos.';
$string['privacy:metadata:pin:lockuntil'] = 'El momento hasta el que se bloquea la comprobación del PIN tras demasiados intentos.';
$string['privacy:metadata:pin:pinhash'] = 'El PIN, guardado solo como un hash seguro.';
$string['privacy:metadata:pin:timemodified'] = 'Cuándo se fijó o cambió el PIN por última vez.';
$string['privacy:metadata:pin:userid'] = 'El usuario al que pertenece el PIN.';
$string['resetpin:confirm'] = '¿Reiniciar tu PIN? La próxima vez que abras la app Aty pondrás uno nuevo.';
$string['resetpin:done'] = 'Tu PIN de Aty se ha reiniciado.';
$string['resetpin:heading'] = 'Reiniciar tu PIN de Aty';
$string['resetpin:nav'] = 'Reiniciar mi PIN de Aty';
$string['resetpin:nopin'] = 'No tienes un PIN de Aty puesto.';
$string['selfservicenotallowed'] = 'El acceso sin contraseña no está disponible para tu cuenta. Entra con tus datos de siempre.';
$string['settings:androidstoreurl'] = 'Enlace de Google Play';
$string['settings:androidstoreurl_desc'] = 'La página de la app en Google Play, que se muestra en el puente de acceso cuando la app no está instalada. Déjalo vacío para ocultarla.';
$string['settings:brandcolour'] = 'Color de marca';
$string['settings:brandcolour_desc'] = 'El color principal del sitio, usado en barras, botones y acentos de la app.';
$string['settings:design'] = 'Diseño de la app';
$string['settings:design_desc'] = 'Cómo se ve este sitio en la app: la pestaña de inicio y la marca. Cada sitio alojado guarda sus propios valores; la app, única, se viste con ellos.';
$string['settings:extracss'] = 'CSS extra de la app';
$string['settings:extracss_desc'] = 'CSS que se sirve a la app después de la capa de marca, para sitios sin Boost Union. En un sitio con Boost Union se usa en su lugar su casilla «CSS adicional para la app móvil» y este se ignora. El modo oscuro actúa sobre la clase <code>.dark</code> de la raíz; carga fuentes con una url() https absoluta.';
$string['settings:homeheading'] = 'Título del inicio';
$string['settings:homeheading_desc'] = 'El título que aparece arriba en la pestaña de inicio. Si se deja vacío, se usa el nombre del sitio.';
$string['settings:homeintro'] = 'Bienvenida del inicio';
$string['settings:homeintro_desc'] = 'Una bienvenida breve, debajo del título, en la pestaña de inicio.';
$string['settings:homelogourl'] = 'Logo del inicio';
$string['settings:homelogourl_desc'] = 'Un logo en blanco que se muestra sobre la cabecera de marca de la pestaña de inicio. Déjalo vacío para mostrar solo el título.';
$string['settings:homeshortcuts'] = 'Accesos del inicio';
$string['settings:homeshortcuts_desc'] = 'Accesos que se muestran en la pestaña de inicio, uno por línea como «Etiqueta | https://url | fas-icono». El icono es opcional, un nombre sólido de Font Awesome que trae la app (por ejemplo fas-calendar); las líneas sin un enlace https válido no se tienen en cuenta.';
$string['settings:iosstoreurl'] = 'Enlace del App Store';
$string['settings:iosstoreurl_desc'] = 'La página de la app en el App Store, que se muestra en el puente de acceso cuando la app no está instalada. Déjalo vacío para ocultarla.';
$string['settings:selfservicecohorts'] = 'Cohortes con acceso de autoservicio';
$string['settings:selfservicecohorts_desc'] = 'Solo los miembros de las cohortes elegidas pueden obtener su propio enlace o QR de acceso («Abrir en la app Aty»). Déjalo todo sin seleccionar para permitir a todos. No afecta a los enlaces emitidos por el personal.';
$string['settings:signin'] = 'Acceso a la app';
$string['settings:signin_desc'] = 'Enlaces y códigos QR que inician la sesión de un usuario en la app sin contraseña. La clave es de un solo uso y caduca en 30 minutos; el token se puede revocar desde Moodle.';
$string['signinfailed'] = 'No se pudo iniciar sesión. El enlace puede haber caducado o haberse usado ya.';
