#Anotaciones Importantes:

- No codifica los controladores con los ROUTES arriba de las funciones, sino que los define creando unas subcarpetas y unos archivos.
  - Resources/config/routing/default.yml (En este carga los controladores, define su método y su route)
  - Resources/config/routing.yml (En este define el archivo a cargar, osea el default)
  Y después modifica el routing del proyecto, agregando estas subcarpetas dentro de cada bundle.
  Sino le agrego la palabra action a las funciones, no me lo toma.

- Crear Bundle(reemplazar BackendBundle por el nombre de nuestro bundle):
  php bin/console generate:bundle --namespace=BackendBundle --format=yml

- Crear DB:
  - Hace un archivo .sql y hace la consulta SQL y después crea la DB en el phpmyadmin pegando ese SQL.
  - Después para crear las entidades, no va una por una, sino que hace un mapping con un comando y se mapea toda esa DB creada por SQL a codigo, especificando en que bundle guardarla.
    Se crean tantos archivos (dentro de la carpeta Resources del Bundle) como tablas tenga esa DB (IMPORTANTE PONERLOS EN SINGULAR, tanto en el archivo como la primer línea de ese archivo y si tiene relaciones también)
  - php bin/console doctrine:mapping:import BackendBundle yml 
    Donde BackendBundle es nuestro bundle deseado e yml el annotation.
  - Para generar las entidades usa el siguiente comando:
    php bin/console doctrine:generate:entities BackendBundle
    Con esto, crea todas las entidades que se mapearon previamente con el comando de arriba

- Crear un servicio nuevo:
  - Crear una carpeta SERVICES dentro del Bundle deseado.
  - Crear un archivo como se lo desee llamar, en este caso será Helpers (por ahora vacío).
  - Luego vamos al services.yml dentro de la carpeta config del proyecto y debajo de todo agregamos el nuevo servicio
  - Volvemos a la clase Helpers (asi se llama nuestro servicio en este caso) y creamos un constructor recibiendo el o los parametros que se envíen desde la línea "arguments" en el archivo services.yml. Por ejemplo en el caso de ahora, se envía el entityManager y se recibe como parámetro.