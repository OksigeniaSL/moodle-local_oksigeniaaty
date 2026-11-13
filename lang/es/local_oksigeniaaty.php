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

$string['appnotallowed'] = 'El plan de tu centro no incluye la app. Puedes seguir usándolo en el navegador.';
$string['appnotallowed:browser'] = 'Abrir en el navegador';
$string['cachedef_sitelist'] = 'La lista de sitios de la app Aty, durante una hora';
$string['cachedef_work'] = 'Cuántas personas tienen la app, para «Tu trabajo»';
$string['calendarapp:address'] = 'Si tu app de calendario te pide la dirección, copia esta:';
$string['calendarapp:disabled'] = 'Tu centro no tiene activada la exportación del calendario.';
$string['calendarapp:google'] = 'Añadir a Google Calendar';
$string['calendarapp:intro'] = 'Añade tu agenda de {$a} al calendario de tu móvil: entregas, clases y eventos, y se mantiene al día sola.';
$string['calendarapp:other'] = 'Añadir a otra app de calendario';
$string['calendarapp:private'] = 'Esta dirección es solo tuya: no la compartas.';
$string['calendarapp:title'] = 'Tu agenda en el móvil';
$string['centreqr:alt'] = 'Código QR de este sitio para la app Aty';
$string['centreqr:download'] = 'Descargar (SVG)';
$string['centreqr:heading'] = 'QR del centro';
$string['centreqr:hint'] = 'El SVG se imprime nítido a cualquier tamaño; impreso, que mida al menos 3 cm de lado. El logo es el logo compacto del sitio o, si no lo hay, el completo (Apariencia > Logos); sin ninguno, el código va sin logo.';
$string['centreqr:intro'] = 'El código de {$a}, con el color y el logo del sitio, para carteles, circulares y la bienvenida. Al escanearlo con la app Aty, se abre la entrada de este sitio; con la cámara del móvil, la web.';
$string['centreqr:menu'] = 'Oksigenia Aty: QR del centro';
$string['channels:app'] = 'Esta app';
$string['channels:appdetail'] = 'Aquí, en tu móvil';
$string['channels:connect'] = 'Conectar';
$string['channels:connectname'] = 'Conectar {$a}';
$string['channels:email'] = 'Correo';
$string['channels:emailoff'] = 'Apagado en tu perfil';
$string['channels:intro'] = 'Estos son los sitios donde {$a} puede avisarte. Conecta los que uses.';
$string['channels:on'] = 'Aquí recibes avisos';
$string['channels:preferences'] = 'Elegir qué avisos van por cada sitio';
$string['channels:title'] = 'Dónde recibir los avisos';
$string['check:appready'] = 'App Aty';
$string['check:appready_androidappid'] = 'El identificador de la app para Android no es «com.oksigenia.aty»: a la gente no se le ofrece la app que toca.';
$string['check:appready_autologin'] = 'El tiempo mínimo entre entradas automáticas pasa de 10 segundos: al abrir desde la app una segunda página web, pide entrar otra vez.';
$string['check:appready_css'] = 'El CSS de la app no es el de este plugin: la app no se viste con la marca del centro.';
$string['check:appready_familyapp'] = 'La app para las familias está encendida, pero le falta algo: la autenticación por servicios web, el esquema de URL o la entrada mediante la app.';
$string['check:appready_listunknown'] = 'No se ha podido leer la lista de sitios de Oksigenia para comprobar que este está en ella.';
$string['check:appready_missing'] = 'A la app Aty le falta algo en este sitio para funcionar entera.';
$string['check:appready_mobileservice'] = 'Los servicios web para móviles están apagados.';
$string['check:appready_notlisted'] = 'Este sitio no está en la lista de sitios de Oksigenia: la app no lo abre. Pídele a Oksigenia que lo añada.';
$string['check:appready_official'] = 'Este sitio usa la app oficial de Moodle: no está en la lista de Aty ni pide Aty.';
$string['check:appready_ok'] = 'Este sitio está listo para la app Aty.';
$string['check:appready_parentrole'] = 'Hay familias, pero Family no tiene rol de padre: los adultos no ven a sus hijos en Moodle.';
$string['check:appready_push'] = 'Faltan ajustes del puente de avisos (dirección, nombre del sitio o clave): no llegan avisos al móvil.';
$string['check:appready_pushapp'] = 'El nombre de la app móvil del puente de avisos no es «com.oksigenia.aty»: Moodle no encuentra los móviles y no avisa a nadie.';
$string['check:appready_scheme'] = 'El esquema de URL de la app no es «oksigeniaaty»: los enlaces y la barra «Abrir en la app» abren la app oficial de Moodle.';
$string['check:appready_settings'] = 'Ajustes de la app móvil';
$string['check:appready_typeoflogin'] = 'El tipo de entrada en la app no es «Mediante la app»: los enlaces sin contraseña fallan.';
$string['check:appready_warnings'] = 'La app Aty funciona en este sitio, pero hay algo que falla sin avisar.';
$string['check:loginform'] = 'Formulario de entrada en la app';
$string['check:loginform_differs'] = 'La página de entrada de la web muestra el formulario de usuario y contraseña porque el tema {$a->theme} tiene su propio interruptor para él y no hace caso de «{$a->setting}», que está desactivado. La app sigue «{$a->setting}» y oculta el formulario: quien tiene una cuenta manual entra en la web, pero no en la app. Activa «{$a->setting}» o, si el formulario no debe verse, oculta también la entrada local en el tema.';
$string['check:loginform_hidden'] = 'El formulario de usuario y contraseña está oculto en la web y en la app.';
$string['check:loginform_same'] = 'La app muestra el formulario de usuario y contraseña.';
$string['compose:confirm'] = '¿Lo publicamos? Les llegará a todos los del curso.';
$string['compose:course'] = 'Curso';
$string['compose:empty'] = 'No puedes escribir a ningún curso.';
$string['compose:intro'] = 'Va a los avisos del curso y les llega a todos los que están en él.';
$string['compose:message'] = 'Mensaje';
$string['compose:publish'] = 'Publicar';
$string['compose:sent'] = 'Publicado. Sale en un momento.';
$string['compose:subject'] = 'Título';
$string['compose:title'] = 'Escribir a un curso';
$string['event:linkissued'] = 'Enlace de entrada a la app enviado';
$string['event:pincleared'] = 'PIN de la app borrado por el centro';
$string['event:pinfailed'] = 'PIN de la app incorrecto';
$string['event:pinlocked'] = 'PIN de la app bloqueado';
$string['expiredkey'] = 'Este enlace de acceso ha caducado. Pide uno nuevo.';
$string['family:add'] = 'Añadir con otro código';
$string['family:addbutton'] = 'Añadir';
$string['family:added'] = 'Añadido. Desliza hacia abajo en el inicio para verlo.';
$string['family:addintro'] = 'Escribe el código de la carta del centro.';
$string['family:code'] = 'Código';
$string['family:grades'] = 'Últimas notas';
$string['family:heading'] = 'Tu familia';
$string['family:linkgone'] = 'Este enlace ya no sirve. Pide un código nuevo al centro.';
$string['family:linktitle'] = 'La app';
$string['family:linkused'] = 'Con este enlace ya entraste en la app. Para usarla en otro móvil, pide un código nuevo al centro.';
$string['family:nosurname'] = '(familia)';
$string['family:notavailable'] = 'Este sitio no tiene fichas de familias.';
$string['family:nothing'] = 'Aún no hay nada que enseñar.';
$string['family:notyours'] = 'No puedes ver a esta persona.';
$string['family:openapp'] = 'Abrir en la app';
$string['family:openappnote'] = 'Desde tu móvil. Entrarás sin contraseña y crearás un PIN para las próximas veces.';
$string['family:toomany'] = 'Demasiados intentos. Espera unos minutos y vuelve a probar.';
$string['forgotpin:emailbody'] = 'Hola {$a->name}:

Usa este enlace para reiniciar tu PIN de Aty y poner uno nuevo. Funciona una sola vez y caduca en 30 minutos:

{$a->link}

Si no lo has pedido tú, puedes ignorar este mensaje.';
$string['forgotpin:emailbutton'] = 'Crear un PIN nuevo';
$string['forgotpin:emailheading'] = 'Crea un PIN nuevo';
$string['forgotpin:emaillead'] = 'Abre este correo en tu móvil y pulsa el botón. La app te pedirá un PIN nuevo.';
$string['forgotpin:emailonce'] = 'El enlace sirve una vez y caduca en 30 minutos.';
$string['forgotpin:emailsubject'] = 'Reinicia tu PIN de Aty';
$string['forgotpin:emailwhy'] = 'Si no lo has pedido, ignora este correo: tu PIN sigue como estaba.';
$string['forgotpin:heading'] = '¿Olvidaste tu PIN de Aty?';
$string['forgotpin:intro'] = 'Escribe tu nombre de usuario o tu correo y te enviaremos un enlace para reiniciar el PIN.';
$string['forgotpin:link'] = '¿Olvidaste el PIN?';
$string['forgotpin:search'] = 'Nombre de usuario o correo';
$string['forgotpin:sent'] = 'Si esa cuenta existe y usa Aty, te hemos enviado por correo un enlace para reiniciar el PIN. Funciona una sola vez y caduca en 30 minutos.';
$string['forgotpin:submit'] = 'Enviar enlace de reinicio';
$string['go:openbtn'] = 'Abrir en la app';
$string['go:title'] = 'Abrir en la app';
$string['go:web'] = 'Seguir en el navegador';
$string['home:day:and'] = '{$a->first} y {$a->last}';
$string['home:day:close'] = '{$a} actividades que cierran';
$string['home:day:close1'] = 'una actividad que cierra';
$string['home:day:dateformat'] = '%e de %B';
$string['home:day:due'] = '{$a} entregas';
$string['home:day:due1'] = 'una entrega';
$string['home:day:event'] = '{$a} eventos';
$string['home:day:event1'] = 'un evento';
$string['home:day:grading'] = 'Tienes {$a} entregas por corregir.';
$string['home:day:grading1'] = 'Tienes una entrega por corregir.';
$string['home:day:live'] = '{$a} clases en directo';
$string['home:day:live1'] = 'una clase en directo';
$string['home:day:next'] = 'Nada para hoy. Lo próximo, el {$a}.';
$string['home:day:nexttomorrow'] = 'Nada para hoy. Lo próximo, mañana.';
$string['home:day:nothing'] = 'Nada para hoy.';
$string['home:day:today'] = 'Hoy tienes {$a}.';
$string['home:day:todo'] = 'Tienes cosas por hacer: {$a}.';
$string['home:day:todo1'] = 'Tienes algo por hacer: {$a}.';
$string['home:day:todomany'] = 'Tienes {$a} cosas por hacer.';
$string['home:day:unread'] = '{$a} conversaciones sin leer';
$string['home:day:unread1'] = 'una conversación sin leer';
$string['home:event:close'] = 'Cierre';
$string['home:event:due'] = 'Entrega';
$string['home:event:expectcompletionon'] = 'Para terminar';
$string['home:event:gradingdue'] = 'Para calificar';
$string['home:event:open'] = 'Apertura';
$string['home:greeting'] = 'Hola, {$a}';
$string['home:hello'] = 'Hola';
$string['home:inminutes'] = 'en {$a} min';
$string['home:join'] = 'Entrar';
$string['home:kind:close'] = 'Cierra';
$string['home:kind:due'] = 'Entrega';
$string['home:messages'] = 'Mensajes';
$string['home:now'] = 'ahora';
$string['home:opensoutside'] = 'Se abre fuera de la app';
$string['home:placeholdernames'] = 'alumno, alumna, estudiante, profesor, profesora, docente, familia, padre, madre, usuario, usuaria, invitado, invitada, demo, prueba';
$string['home:seeall'] = 'Ver todo';
$string['home:shortcuts'] = 'También';
$string['home:submission'] = 'entrega';
$string['home:submissions'] = 'entregas';
$string['home:tab'] = 'Inicio';
$string['home:today'] = 'Hoy';
$string['home:tograde'] = 'Por corregir';
$string['home:unread'] = 'sin leer';
$string['home:upcoming'] = 'Próximos';
$string['invalidkey'] = 'Este enlace de acceso no es válido.';
$string['issued:body'] = 'Hola, {$a->firstname}:

Para entrar en la app de {$a->site} sin contraseña, abre este enlace en tu móvil. Sirve una vez, hasta el {$a->expires}:

{$a->url}

Si aún no tienes la app, el enlace te lleva a ella. Después, entras con tu PIN.';
$string['issued:button'] = 'Abrir la app';
$string['issued:heading'] = 'Ya puedes usar la app de {$a->site}';
$string['issued:hello'] = 'Hola, {$a->firstname}:';
$string['issued:lead'] = 'Abre este correo en tu móvil y pulsa el botón. Entrarás sin contraseña y solo tendrás que crear un PIN para las próximas veces.';
$string['issued:noapp'] = 'Si aún no tienes la app, el botón te lleva a ella.';
$string['issued:once'] = 'El enlace sirve una vez, hasta el {$a->expires}. Es solo tuyo: no lo reenvíes.';
$string['issued:subject'] = 'Tu entrada a la app de {$a->site}';
$string['issued:why'] = 'Te lo manda {$a->site}. Si no lo esperabas, puedes ignorar este correo.';
$string['issuenotallowed'] = 'No se puede sacar un enlace de entrada para esta cuenta: las de administración, suspendidas o borradas entran siempre con su contraseña.';
$string['mobilenotenabled'] = 'El acceso desde el móvil no está activado en este sitio.';
$string['mylink:expires'] = 'Este enlace funciona una sola vez y caduca en 30 minutos. Recarga esta página para obtener uno nuevo.';
$string['mylink:heading'] = 'Abrir en la app Aty';
$string['mylink:intro'] = 'Pulsa el botón en tu móvil para abrir la app Aty con la sesión ya iniciada. No hace falta contraseña.';
$string['mylink:nav'] = 'Abrir en la app Aty';
$string['mylink:qralt'] = 'Código QR para entrar en la app Aty';
$string['mylink:qrhint'] = 'O escanea este código con la app Aty en tu móvil.';
$string['noios'] = 'En iPhone y iPad, de momento sigues usando la plataforma en el navegador: la app es para Android.';
$string['oksigeniaaty:issuelink'] = 'Emitir un enlace o QR de acceso a Aty para otro usuario';
$string['open:android'] = 'Disponible en Google Play';
$string['open:expired'] = 'Este enlace de acceso ha caducado o ya se ha usado. Pide uno nuevo a tu centro u organización.';
$string['open:gotosite'] = 'Ir a la plataforma';
$string['open:ios'] = 'Descárgala en el App Store';
$string['open:noapp'] = '¿Todavía no tienes la app?';
$string['open:openbtn'] = 'Abrir en Aty';
$string['open:opening'] = 'Abriendo la app...';
$string['open:title'] = 'Entrar en Aty';
$string['openinapp:hide'] = 'Ocultar durante 30 días';
$string['openinapp:label'] = 'App';
$string['openinapp:open'] = 'Abrir';
$string['openinapp:text'] = 'Abre esta página en la app';
$string['pinbiometric'] = 'Confirma tu identidad';
$string['pinbiometricask'] = '¿Usar tu huella o cara para entrar la próxima vez?';
$string['pincancel'] = 'Cancelar';
$string['pinconfirm'] = 'Vuelve a escribirlo para comprobar que está bien.';
$string['pininvalid'] = 'El PIN debe tener de 4 a 8 cifras.';
$string['pinlocked'] = 'Demasiados PIN incorrectos. Espera unos minutos y vuelve a intentarlo, o pide un enlace de acceso nuevo.';
$string['pinmismatch'] = 'No coincide. Escribe otra vez el mismo PIN.';
$string['pinnotsaved'] = 'No se ha podido guardar el PIN. Has entrado igual; puedes crearlo la próxima vez que entres con un enlace o un código.';
$string['pinokconfirm'] = 'Confirmar PIN';
$string['pinokenter'] = 'Entrar';
$string['pinoksetup'] = 'Crear PIN';
$string['pinsetup'] = 'Pon un PIN de 4 a 8 cifras para entrar más rápido la próxima vez. Puedes omitirlo y ponerlo más tarde.';
$string['pinskip'] = 'Omitir';
$string['pintitleconfirm'] = 'Repite tu PIN';
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
$string['selfservicenotforadmins'] = 'Por seguridad, las cuentas de administración no pueden entrar en la app sin contraseña. En la app, entra con tu usuario y tu contraseña.';
$string['sendapp:again'] = 'Volver a mandar';
$string['sendapp:alldone'] = 'Todos los de la lista han entrado en la app o tienen su enlace en camino.';
$string['sendapp:ask'] = '¿A quién se la mandas?';
$string['sendapp:badgedone'] = 'todos';
$string['sendapp:badgewaiting'] = 'faltan';
$string['sendapp:badgewaitingone'] = 'falta';
$string['sendapp:blockedadmin'] = 'Administración: entra con contraseña';
$string['sendapp:blockednoemail'] = 'Sin correo';
$string['sendapp:chosendetail'] = '{$a->area} · {$a->people} personas';
$string['sendapp:chosendetailone'] = '{$a->area} · 1 persona';
$string['sendapp:clearpin'] = 'Borrar el PIN';
$string['sendapp:cohortdetail'] = 'Cohorte · {$a} personas';
$string['sendapp:cohortdetailone'] = 'Cohorte · 1 persona';
$string['sendapp:cohorts'] = 'Cohortes';
$string['sendapp:confirm'] = 'Se mandará un correo con su propio enlace a {$a} personas que aún no han entrado en la app. Cada enlace sirve una vez, durante 7 días. ¿Los mandamos?';
$string['sendapp:confirmone'] = 'Se mandará un correo con su propio enlace a la persona que aún no ha entrado en la app. El enlace sirve una vez, durante 7 días. ¿Lo mandamos?';
$string['sendapp:countblocked'] = 'no pueden recibir enlace';
$string['sendapp:countblockedone'] = 'no puede recibir enlace';
$string['sendapp:countinapp'] = 'en la app';
$string['sendapp:countsent'] = 'con enlace';
$string['sendapp:countwaiting'] = 'faltan';
$string['sendapp:countwaitingone'] = 'falta';
$string['sendapp:coursedetail'] = '{$a->area} · {$a->people} personas · {$a->inapp} en la app';
$string['sendapp:coursedetailone'] = '{$a->area} · 1 persona · {$a->inapp} en la app';
$string['sendapp:courses'] = 'Cursos';
$string['sendapp:failed'] = 'No se han podido mandar: {$a}.';
$string['sendapp:fixemail'] = 'Ponerle un correo';
$string['sendapp:group'] = 'Grupo';
$string['sendapp:groupblocked'] = 'No pueden recibir enlace';
$string['sendapp:groupinapp'] = 'Ya en la app';
$string['sendapp:groupsent'] = 'Con su enlace en camino';
$string['sendapp:groupwaiting'] = 'Faltan';
$string['sendapp:heading'] = 'Mandar la app';
$string['sendapp:intro'] = 'Manda a cada persona un correo con su propio enlace: lo abre en el móvil y entra en la app sin contraseña. El enlace solo le llega a esa persona; tú no lo ves.';
$string['sendapp:menu'] = 'Oksigenia Aty: mandar la app';
$string['sendapp:nobody'] = 'Aquí no hay nadie. Prueba con otra opción.';
$string['sendapp:noresults'] = 'Nada con «{$a}». Prueba con una parte del nombre, o con el del curso.';
$string['sendapp:notsent'] = 'No se ha podido mandar. Si se le mandó hace menos de una hora, espera un poco.';
$string['sendapp:other'] = 'Elegir otro';
$string['sendapp:people'] = 'Personas';
$string['sendapp:pincleared'] = 'PIN borrado: la próxima vez que entre con un enlace, pondrá uno nuevo.';
$string['sendapp:pinnotcleared'] = 'No se ha borrado ningún PIN.';
$string['sendapp:queued'] = '{$a} correos en camino. Tardan unos minutos; esta lista se actualiza a medida que salen.';
$string['sendapp:results'] = 'Con «{$a}»';
$string['sendapp:running'] = 'Cursos en marcha';
$string['sendapp:runninghint'] = 'Primero, los que tienen más gente sin la app.';
$string['sendapp:searchhint'] = 'Un curso, una persona o una cohorte: con una parte del nombre basta.';
$string['sendapp:searchhintcohorts'] = 'Un curso o una cohorte: con una parte del nombre basta.';
$string['sendapp:searchhintcourses'] = 'Un curso: con una parte del nombre basta.';
$string['sendapp:searchhintpeople'] = 'Un curso o una persona: con una parte del nombre basta.';
$string['sendapp:seeall'] = 'Ver la lista entera ({$a})';
$string['sendapp:send'] = 'Mandar';
$string['sendapp:sendall'] = 'Mandar a los {$a} que faltan';
$string['sendapp:sendallone'] = 'Mandar al que falta';
$string['sendapp:sentmany'] = 'Mandados: {$a}.';
$string['sendapp:sentone'] = 'Mandado.';
$string['sendapp:stateexpired'] = 'El enlace caducó el {$a}';
$string['sendapp:stateinapp'] = 'En la app';
$string['sendapp:statesent'] = 'Enlace mandado, sirve hasta el {$a}';
$string['sendapp:stateuntil'] = 'Sirve hasta el {$a}';
$string['sendapp:statewaiting'] = 'Aún sin enlace';
$string['sendapp:task'] = 'Mandar la app a un grupo de personas';
$string['sendapp:truncated'] = 'Se muestran las {$a} primeras personas.';
$string['sendapp:wholecourse'] = 'Todo el curso';
$string['sendapp:yes'] = 'Sí, mandarlos';
$string['sendapp:yesone'] = 'Sí, mandárselo';
$string['settings:androidstoreurl'] = 'Enlace de Google Play';
$string['settings:androidstoreurl_desc'] = 'La página de la app en Google Play, que se muestra en el puente de acceso cuando la app no está instalada. Déjalo vacío para ocultarla.';
$string['settings:brandcolour'] = 'Color de marca';
$string['settings:brandcolour_desc'] = 'El color principal del sitio, usado en barras, botones y acentos de la app.';
$string['settings:content'] = 'Contenido en la app';
$string['settings:content_desc'] = 'Lo que la app puede abrir en el propio teléfono, sin la web dentro de un marco.';
$string['settings:design'] = 'Diseño de la app';
$string['settings:design_desc'] = 'Cómo se ve este sitio en la app: la pestaña de inicio y la marca. Cada sitio alojado guarda sus propios valores; la app, única, se viste con ellos.';
$string['settings:extracss'] = 'CSS extra de la app';
$string['settings:extracss_desc'] = 'CSS que se sirve a la app después de la capa de marca, para sitios sin Boost Union. En un sitio con Boost Union se usa en su lugar su casilla «CSS adicional para la app móvil» y este se ignora. El modo oscuro actúa sobre la clase <code>.dark</code> de la raíz; carga fuentes con una url() https absoluta.';
$string['settings:family'] = 'Familias';
$string['settings:family_desc'] = 'Para los sitios con Oksigenia Family: lo que ve un adulto en la app sobre los hijos de su familia.';
$string['settings:familyagenda'] = 'Lo próximo';
$string['settings:familyagenda_desc'] = 'Las fechas de los cursos y grupos del hijo, durante las dos próximas semanas. No sus entradas personales del calendario.';
$string['settings:familyapp'] = 'Las familias entran en la app';
$string['settings:familyapp_desc'] = 'Quien reclama el código de un hijo ve un botón para abrir la app, y entra sin contraseña con una cuenta hecha para él, que no puede entrar por la web. Al encenderlo se enciende también la autenticación por servicios web del sitio, que esas cuentas necesitan. Solo funciona con la app Aty en el sitio: los servicios web para móviles, el esquema de URL «oksigeniaaty» y la entrada por la propia app (ajustes de la app móvil).';
$string['settings:familygrades'] = 'Últimas notas';
$string['settings:familygrades_desc'] = 'Las últimas notas del hijo en actividades, sin lo que el curso oculte. Apagado hasta que el centro decida enseñarlas.';
$string['settings:h5pinapp'] = 'Los H5P se juegan en la app';
$string['settings:h5pinapp_desc'] = 'La app juega un H5P en el teléfono, también sin conexión, solo si su descarga está permitida. Si no, muestra la página de la web dentro de un marco, y en un iPhone ese marco enseña la página de entrar en lugar del contenido. Activado, permite la descarga de todos los H5P que tienen oculta la barra de acciones: sin la barra no aparece ningún botón de descarga en la web, así que no cambia nada para quien la usa. Los que muestran la barra se quedan como los dejó su autor. Una tarea revisa cada diez minutos el contenido nuevo.';
$string['settings:homelogourl'] = 'Logo del inicio';
$string['settings:homelogourl_desc'] = 'Un logo en blanco que se muestra sobre la cabecera de marca de la pestaña de inicio. Déjalo vacío para mostrar solo el título.';
$string['settings:homeshortcuts'] = 'Accesos del inicio';
$string['settings:homeshortcuts_desc'] = 'Accesos que se muestran en la pestaña de inicio, uno por línea como «Etiqueta | https://url | fas-icono». El icono es opcional, un nombre sólido de Font Awesome que trae la app (por ejemplo fas-calendar); las líneas sin un enlace https válido no se tienen en cuenta.';
$string['settings:iosstoreurl'] = 'Enlace del App Store';
$string['settings:iosstoreurl_desc'] = 'La página de la app en el App Store, que se muestra en el puente de acceso cuando la app no está instalada. Déjalo vacío para ocultarla.';
$string['settings:openinappbar'] = 'Ofrecer abrir las páginas en la app';
$string['settings:openinappbar_desc'] = 'En los móviles Android, una barra fina al pie de cada página ofrece abrir esa misma página en la app que usa el sitio (Aty, o la app oficial de Moodle, según los ajustes de la app móvil). Cada persona puede ocultarla durante 30 días. Enciéndela cuando la gente del sitio ya sepa que tiene la app.';
$string['settings:selfservicecohorts'] = 'Cohortes con acceso de autoservicio';
$string['settings:selfservicecohorts_desc'] = 'Solo los miembros de las cohortes elegidas pueden obtener su propio enlace o QR de acceso («Abrir en la app Aty»). Déjalo todo sin seleccionar para permitir a todos. No afecta a los enlaces emitidos por el personal.';
$string['settings:signin'] = 'Acceso a la app';
$string['settings:signin_desc'] = 'Enlaces y códigos QR que inician la sesión de un usuario en la app sin contraseña. Cada clave sirve una vez: la que cada persona saca en la web caduca en 30 minutos, y la que manda el centro, en 7 días. El token se puede revocar desde Moodle.';
$string['signinfailed'] = 'No se pudo iniciar sesión. El enlace puede haber caducado o haberse usado ya.';
$string['task:h5pinapp'] = 'Hacer que los H5P se jueguen en la app';
$string['work:qr'] = 'QR del centro';
$string['work:status'] = '{$a} personas con la app';
$string['work:status:one'] = '{$a} persona con la app';
$string['work:title'] = 'App móvil';
