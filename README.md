Para instalar o pacote execute o comando:<br>
composer require fabrizioquadro/importarxml<br>
<br>
logo após copia a linha a seguir para a parte de providers do arquivo config/app.php <br>
fabrizioquadro\importarxml\ImportarXmlServiceProvider::class,<br>
<br>
após isso execute na linha de comando o comando<br>
php artisan vendor:publish <br>
publique as migratios desse pacote para o seu diretório de migratios<br>
logo após rode o comando <br>
php artisan migrate<br>

//para acessar a pagina principal da importação acesse<br>
caminhodosistema/importarxml

