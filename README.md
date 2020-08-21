# HitesPay

## Compatibilidad
El plugin esta desarrollado con las siguientes dependencias:

* PHP ^7.0
* GuzzleHttp ^7.0

## Algunos alcances técnicos

Para utilizar hitesPay se debe contar con un certificado asimétrico que debes proveer, ya que de esta manera se realiza el encriptado de la respuesta final (confirmaTrx).

Este certificado es Autogenerado y en formato DER, por lo cual debes generarlo con OpenSSL con los siguientes comandos:

Clave Privada:

     openssl genpkey -algorithm RSA -out private_key.pem
  
Clave Pública

    openssl rsa -pubout -in private_key.pem -out public_key.pem

Finalmente, la clave pública debes transformarla a formato DER con el siguiente comando (esta es la que enviarás a Hites)

    openssl rsa -pubin -in public_key.pem -outform der -out public_key.der

## Uso

### Para crear una instancia de Hitespay, se debe invocar de la siguiente manera:

    $hitesPay = new HitesPay($cc, $cl, $pk, $ru, [$env]);

Donde:

  * $cc = Codigo de Comercio entregado por Hites
  * $cl = Código de local, por lo general es 1,
  * $pk = Llave privada entregada a Hites en formato PEM, esta llave es la que firma el retorno de hites con los datos de la transacción.
  * $ru = Url de retorno, usada por Hites para retornar a tu página y así cerrar la transacción.
  * $env = Indica en que ambiente trabajaran las transacciones. las opciones son "testing" para ambiente de pruebas, o "prod" para ambiente productivo.

De no haber problema, retornara un objeto de pago de Hites.

### Para iniciar un pago

     $pago = $hitesPay->initPayment($monto);

Donde:

     $monto = monto de la transacción;
 
De ejecutarse correctamente, retornará un arreglo con la llave "status" con valor "ok", y la llave "response" con un arreglo con los valores de token y paymentUrl.

   * token = Corresponde al valor usado para revisar si el pago se realizó correctamente en Hites
   * paymentUrl = Es la URL de Hites donde se realizará el pago.

### Validar un pago

    $validaPago = $hitesPay->checkPayment($token);

Donde:
     
     $token = Token capturado al inicio del proceso de pago.

De haber ejecutado correctamente el pago Hites, se retornara un array con la llave "status" con valor "ok", y la llave "response" conteniendo un array con la información del pago:
  
  * cantidadCuotas: Cuotas en que se pacto la transacción
  * codAutorización: Código de autorización entregado por Hites
  * fechaPago: Fecha que se ejecutó el pago
  * horaPago: Hora de pago
  * mensajeRetorno: Mensaje Enviado por Hites
  * montoTotal: Monto pagado a Hites por la Transacción (cuando el pago es en cuotas, el monto no coincide con el monto enviado)
  
 
