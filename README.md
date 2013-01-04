# Facebook Plugin for CakePHP

DataSource and oAuth for CakePHP 2.x based on Facebook API

##Goal</h3>

To make an abstract layer between CakePHP and Facebook, so anyone that knows CakePHP can easily build apps for Facebook without knowing the API.

## Installation
**Step 1.** Download, unzip and rename the folder to Facebook and move it to the Plugin's folder of your CakePHP project.

**Step 2.** In Config/bootstrap.php, enable the plugin and it's bootstrap, by adding a line like this:

    CakePlugin::loadAll(
        array('Facebook' => array('bootstrap' => true))
    );

**Step 3.** You must have a table for Users and a model, with at least one field for "uid". If you don't have one, something like this is fine:

Table:

    CREATE TABLE users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50),
        uid VARCHAR(50)
    );

Model:

    <?php
    App::uses('AppModel', 'Model');

    class User extends AppModel {
    }
    ?>

**Step 4.** Enable Auth component in AppController.php and link it to Facebook Authentication. For example:

    public $components = array(
        'Session',
        'Auth' => array(
            'loginAction' => array(
                'plugin' => 'facebook',
                'controller' => 'users',
                'action' => 'login'
            ),
            'loginRedirect' => '/',
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
        'app_secret' => '6059c46e362346xxxxxxxxxxxx'
    );

You're ready!! Now proceed with the usage section.

## Usage
Here are some examples to learn the basis:

**Example 1.** How to natively login to CakePHP Auth using Facebook:

    // In the Controller include FacebookHelper
    public $helpers = array('Facebook.Facebook');

    // In the View (probably in Users/login.ctp) print the login button
    echo $this->Facebook->loginButton();

    // In the View print the logout button
    echo $this->Html->link('Logout', array('plugin' => 'facebook', 'controller' => 'users', 'action' => 'logout'));

By default, the above example will print a raw link with the label "Login". If you want to customize it, you can easily do it by passing some params:

    // The text of the link
    $label = "Facebook Login!";

    // The same options as HtmlHelper::link()
    $options = array(
        'class' => 'btn_login',
        'id' => 'facebook'
    );

    // The permissions we need from the user
    $permissions = array('email','user_photos');

    echo $this->loginButton($label, $options, $permissions);

**Example 2.** Get all the albums from the user:

    // In the Controller include the FacebookAlbum model
    public $uses = array('Facebook.FacebookAlbum');
	
    // In the same Controller, in an action
    $albums = $this->FacebookAlbum->find('all', array('fields' => array('FacebookAlbum.name'), 'conditions' => array('FacebookAlbum.owner' => $this->Auth->User('uid'))));
    $this->set(compact('albums'));

    // In the view from that action
    foreach ($albums as $album) {
        echo $album['FacebookAlbum']['name']." - ";
    }
	
**More information**

Some points to take into account:

* From the CRUD, only R works. This means that only reads.
* For reading, only supports the methods $model->find('all', $options); and $model->find('first', $options);
* You **must always** include the classname in fields and conditions (e.g. "FacebookAlbum.name", not just "name")
* The models are taken from the [FQL tables](https://developers.facebook.com/docs/reference/fql/). The supported ones are:
 * FacebookUser (user)
 * FacebookAlbum (album)
 * FacebookPhoto (photo)
 * FacebookPage (page)
 * FacebookPageAdmin (page_admin)
* Models are related using [CakePHP Model relations](http://book.cakephp.org/2.0/en/models/associations-linking-models-together.html).


## How to Contribute
If you want to contribute please get in touch through the [support section](http://marianofino.github.com/Facebook-Plugin-for-CakePHP/#comments), by [twitter](https://twitter.com/finomdq) or [github](https://github.com/marianofino). Also, you can fix or submit new issues on [github](https://github.com/marianofino/Facebook-Plugin-for-CakePHP/issues).

## Authors and Contributors
Copyright (c) 2012 - Mariano Finochietto // twitter: [@finomdq](https://twitter.com/finomdq) // github: [@marianofino](https://github.com/marianofino).

The Auth component is based on the danielauener's "FacebookAuthenticate". You can find more information here: [https://github.com/danielauener/cake-social-custom-auth](https://github.com/danielauener/cake-social-custom-auth)

## License
This software is released under the [GNU LGPL License](http://www.gnu.org/licenses/lgpl-3.0.txt).
