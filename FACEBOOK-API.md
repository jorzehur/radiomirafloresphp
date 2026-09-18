# Conectar la web con Facebook (traer el texto de los posts solo)

Guía para cuando quieras que las noticias se lean completas en tu web **sin pegar el texto a mano
cada día**.

**Hoy no hace falta nada de esto**: pegas el enlace en el Dashboard y ya se publica; si además pegas
el texto, la noticia se lee completa en tu web. Esta guía es para automatizarlo más adelante.

---

## 1. Lo que se puede y lo que no

| Quiero... | ¿Hace falta token de Facebook? |
|---|---|
| Mostrar el post incrustado (lo que ya haces) | **No** — el plugin `plugins/post.php` funciona sin token |
| Saber si el post se puede mostrar (lo que ya hace la web) | **No** |
| **Leer el texto y la imagen del post desde la API** | **Sí** |
| Publicar en tu página desde la web | **Sí**, y con permisos extra |

Es decir: todo lo que ves hoy funciona sin token. El token solo sirve para traerte el texto solo.

---

## 2. Camino A — Graph API con token de página (**el recomendado para tu propia página**)

Es el camino que **no** exige revisión de la aplicación, porque solo accede a **tu** página.

### Permisos que hacen falta

- `pages_show_list` — para listar tus páginas y obtener el identificador
- `pages_read_engagement` — para leer las publicaciones de la página

### Paso a paso

1. **Crea la app**: entra en <https://developers.facebook.com/apps> → *Crear aplicación*.
   - Tipo: **Empresa / Business** (no "Consumidor").
   - Rellena el nombre y el correo de contacto.
2. **Ten la página en tu portafolio de negocio**: entra en <https://business.facebook.com> →
   *Configuración del negocio*. Si tu página no aparece en *Cuentas → Páginas*, añádela.
3. **Crea un usuario del sistema** (es la clave para un token que **no caduca**):
   - *Configuración del negocio → Usuarios → Usuarios del sistema* → **Añadir**.
   - Nombre: por ejemplo `web-radio`. Rol: **Administrador** (o Empleado).
   - **Asignar activos** → pestaña *Páginas* → selecciona tu página → **Control total** → Guardar.
4. **Genera el token**:
   - Con el usuario del sistema seleccionado, pulsa **Generar token nuevo**.
   - Elige tu aplicación.
   - Marca los permisos: `pages_show_list` y `pages_read_engagement`
     (si además quieres estadísticas, añade `read_insights`).
   - Copia el token y **guárdalo en un lugar seguro**: no caduca, pero si lo pierdes hay que
     generar otro.
5. **Consigue el ID de tu página**:
   - En el Graph API Explorer (<https://developers.facebook.com/tools/explorer>) pide
     `me/accounts?fields=id,name` con tu token: ahí aparece el `id` de la página.
6. **Pruébalo** en el Explorer (o con curl):

   ```
   https://graph.facebook.com/v21.0/TU_PAGE_ID/posts?fields=message,created_time,permalink_url,full_picture&limit=5&access_token=TU_TOKEN
   ```

   Si devuelve un JSON con tus publicaciones (`message`, `permalink_url`, `full_picture`), ya está.

### Comprobar el token

- <https://developers.facebook.com/tools/debug/accesstoken/> — pega el token y mira:
  - **Caduca**: debe decir *Nunca*.
  - **Permisos**: deben aparecer los que marcaste.
- Si algo falla, el propio depurador dice qué permiso falta.

---

## 3. Camino B — Meta oEmbed (`oembed_post`)

Meta tiene un endpoint específico para *incrustar* publicaciones:

```
https://graph.facebook.com/v21.0/oembed_post?url=<URL_DEL_POST>&access_token=TU_TOKEN
```

**Requiere la función `oEmbed Read` y pasar revisión de la aplicación (App Review).** Aquí es donde
mucha gente se atasca: hay casos de gente aprobada que sigue recibiendo
*"your use of this endpoint must be reviewed and approved by Facebook"*.

Solo merece la pena si necesitas leer o incrustar publicaciones de páginas **que no son tuyas**.
Para la tuya, usa el Camino A.

Fuentes de lo que se comenta sobre esto:

- [Meta Graph API: how to get a permanent access token](https://stackoverflow.com/questions/79501605/meta-graph-api-how-to-get-a-permanent-access-token-for-internal-daemon-app)
- [oEmbed Read: can't request advanced access](https://stackoverflow.com/feeds/question/79285083)
- [Approved for oEmbed but the endpoint still says it must be reviewed](https://stackoverflow.com/feeds/question/73177054)
- [Meta oEmbed Read explicado](https://www.bluehost.com/blog/meta-oembed-read-explained/)

---

## 4. Dónde se guarda el token (seguridad)

**Nunca** en el HTML, ni en una página pública, ni en el repositorio de git.

En este proyecto, igual que las credenciales de MySQL:

1. En local: variables de entorno, o un archivo fuera de la carpeta web.
2. En el hosting: variables de entorno del panel (o `config.php` editado a mano y **sin subir a git**).
3. El `.gitignore` ya ignora `.env` y `*.sql`; añade el archivo donde pongas el token si es otro.

Y siempre: si algún día sospechas que se ha filtrado, en el mismo sitio donde lo generaste puedes
**revocarlo** y crear otro en 30 segundos.

---

## 5. Qué habría que programar en la web (cuando tengas el token)

Ya está pensado para encajar sin cambiar nada de lo actual:

1. Añadir a `config.php`: `FB_PAGE_ID` y `FB_TOKEN` (desde variables de entorno).
2. Crear `includes/facebook.php` con una función tipo
   `facebook_ultimas_publicaciones(int $cuantas): array` que devuelva
   `titulo` (primeras palabras del mensaje), `texto` (el mensaje completo), `url` (permalink) e
   `imagen` (`full_picture`).
3. Dos formas de usarlo:
   - **Botón en el Dashboard**: *"Traer las últimas publicaciones"* → muestra las 5 más recientes →
     añades la que quieras con el texto ya relleno (un clic).
   - **Sincronización automática**: una tarea programada (cron del hosting) que una vez al día traiga
     las publicaciones nuevas y las guarde como noticias.
4. **Guardar el texto en la base de datos**, no pedírselo a Facebook en cada visita: así la web sigue
   funcionando rápido y no depende de que Facebook esté disponible.
5. **Degradación elegante**: si el token caduca o Facebook falla, la web debe seguir mostrando el post
   incrustado (lo que ya hace hoy) y avisar en el panel.

Con eso, publicar la noticia del día sería: entrar al panel y pulsar un botón.

---

## 6. Avisos honestos

- **Meta cambia esto a menudo**: los nombres de los menús y los permisos cambian de un año a otro.
  Si algo no coincide, manda la documentación oficial, no esta guía:
  <https://developers.facebook.com/docs/pages-api> y
  <https://developers.facebook.com/docs/permissions>.
- **Los tokens se pueden invalidar** sin avisar: si cambias la contraseña, si cambia el
  administrador de la página, si Meta restringe la app... Por eso conviene el plan B (la web
  funcionando sin token) que ya tienes.
- **Límites de uso**: la API tiene límites por hora. Para una radio con pocas publicaciones al día no
  es un problema, pero la sincronización debe ser espaciada (una vez al día o cada pocas horas).
- **Nada de esto es obligatorio**: sin token, tu web funciona igual de bien; solo te ahorra pegar el
  texto a mano.
