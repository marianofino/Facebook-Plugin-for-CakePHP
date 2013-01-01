# Facebook Plugin for CakePHP

DataSource and oAuth for CakePHP 2.x based on Facebook API

## Installation
**Step 1.** Download, unzip and rename the folder to Facebook and move it to the Plugin's folder of your CakePHP project.

**Step 2.** In Config/bootstrap.php, enable the plugin and it's bootstrap, by adding a line like this:

    CakePlugin::loadAll(
        array('Facebook' => array('bootstrap' => true))
    );

**Step 3.** You must have a table for Users, with at least one field for "username". Something like this is fine:

    CREATE TABLE users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50)
    );

**Step 4.** Enable Auth component in AppController.php and link it to Facebook Authentication. For example:

    public $components = array(
        'Session',
        'Auth' => array(
            'loginRedirect' => array('controller' => 'users', 'action' => 'view'),
            'logoutRedirect' => '/',
            'authenticate' => array(
                'all' => array('userModel' => 'User'),
                'Facebook.Oauth'
            )
        )
    );

**Step 5.** In Config/database.php, enter the AppKey and AppSecret from your Facebook App, your app complete url, and your login action:

    public $facebook = array(
        'datasource' => 'Facebook.FQL',
        'app_url' => 'http://www.yourdomain.com/path/to/cake', // or just http://www.yourdomain.com if it's on the root
        'app_id' => '35868871xxxxxx',
        'app_secret' => '6059c46e362346xxxxxxxxxxxx',
        'login_url' => array('controller' => 'users', 'action' => 'login') // probably right
    );

You're ready!! Now proceed with the tutorial.

## Usage
This is a tiny tutorial to learn the basis:

**Step 1.** Create a controller with some default actions for login() and logout(). Also we will add some method to test the API. Create UsersController.php like this:

    <?php
        App::uses('AppController', 'Controller');
        class UsersController extends AppController {
            public $uses = array('Facebook.FacebookUser');
            public $helpers = array('Facebook.Facebook','Session','Html');

            public function login() {
                if ($this->Auth->login()) {
                    $this->redirect($this->Auth->redirect());
                }
            }

            public function logout() {
                $this->redirect($this->Auth->logout());
            }
	
            public function view() {
                $userInfo = $this->FacebookUser->find('all', array('conditions' => array('username' => $this->Auth->User('username')), 'fields' => array('first_name','last_name')));
                $this->set(compact('userInfo'));
            }
        }
    ?>

**Step 2.** Create a simple model, like this:

    <?php
        App::uses('AppModel', 'Model');

        class User extends AppModel {
        }
    ?>

**Step 3.** Create 2 views. One in Users/login.ctp and the other in Users/view.ctp:

login.ctp

    <?php
        echo $this->Facebook->loginButton('Login');
    ?>
view.ctp

    <?php
        echo "Hello ".$userInfo['FacebookUser']['first_name']." ".$userInfo['FacebookUser']['last_name'];
        echo $this->Html->link('Logout', array('controller' => 'users', 'action' => 'logout'));
    ?>

Congratulations! Now go to www.yourdomain.com/path/to/cake/ and test that everything works as expected!

**Appendix**
For now, you can only read information, by using the method find('all', $options). For more information you can look at the FQL reference. Another limitation, is that only supports "user" table by now.

For example, to read information from the FQL table "user", you can do in any controller:

    $uses = array('Facebook.FacebookUser');
    public function viewUser() {
        $options = array(
            'fields' => array('username','first_name','last_name'),
            'conditions' => ('id' => '1390757189')
        );
        $userInfo = $this->FacebookUser->find('all', $options);
    }

## How to Contribute
If you want to contribute please get in touch through the [support section](http://marianofino.github.com/Facebook-Plugin-for-CakePHP/#comments), by [twitter](https://twitter.com/finomdq) or [github](https://github.com/marianofino). Also, you can fix or submit new issues on [github](https://github.com/marianofino/Facebook-Plugin-for-CakePHP).

## Authors and Contributors
Copyright (c) 2012 - Mariano Finochietto // twitter: [@finomdq](https://twitter.com/finomdq) // github: [@marianofino](https://github.com/marianofino).

The Auth component is based on the danielauener's "FacebookAuthenticate". You can find more information here: [https://github.com/danielauener/cake-social-custom-auth](https://github.com/danielauener/cake-social-custom-auth)

## License
This software is released under the [GNU LGPL License](http://www.gnu.org/licenses/lgpl-3.0.txt).
