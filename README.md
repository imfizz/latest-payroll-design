# JTDV Admin System

![JTDV Security Agency](jtdv-admin-dashboard.png?raw=true "JTDV Security Agency")

### <a href="https://jtdv.tech/login.php">LIVE DEMO</a>

## System Description

Employee, Company, and Secretary Accounts are created through the JTDV Admin System. It also keeps track of personnel who have been allocated to a given firm. It is also responsible for accepting or denying an employee's request for leave. This also manages to track an employee's infractions and notify them.

## Guide to install in your local machine.

### 1. Make sure you have XAMPP installed!

To begin, you must first download and install xampp on your local computer. Simply click the config and then the PHP(php.ini) file once it has been installed.

Search for ```;extension=openssl``` and scroll down a little to find the code below. Make sure you format it according to these guidelines.

```;extension=pdo_firebird``` <br/>
```extension=pdo_mysql```<br/>
```;extension=pdo_oci```<br/>
```extension=pdo_odbc```<br/>
```extension=pdo_pgsql```<br/>
```extension=pdo_sqlite```<br/>

### 2. Install Composer.exe

#### <a href="https://getcomposer.org/download/">Click and install this</a>
