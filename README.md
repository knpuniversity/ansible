# Ansible for Automation!

Well hi there! This repository holds the code and script for the
[Animated Deployment with Ansistrano](https://knpuniversity.com/screencast/ansistrano)
course on KnpUniversity.

## Setup

If you've just downloaded the code, congratulations!

To get it working, follow these steps:

**Setup parameters.yml**

First, make sure you have an `app/config/parameters.yml`
file (you should). If you don't, copy `app/config/parameters.yml.dist`
to get it:

```
cp app/config/parameters.yml.dist app/config/parameters.yml
```

Next, look at the configuration and make any adjustments you
need (like `database_password`).

**Download Composer dependencies**

Make sure you have [Composer installed](https://getcomposer.org/download/)
and then run:

```
composer install
```

You may alternatively need to run `php composer.phar install`, depending
on how you installed Composer.

**Setup the Database**

Again, make sure `app/config/parameters.yml` is setup
for your computer. Then, create the database and the
schema!

```
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console hautelook_alice:doctrine:fixtures:load
```

If you get an error that the database exists, that should
be ok. But if you have problems, completely drop the
database (`doctrine:database:drop --force`) and try again.

**Build Assets**

The assets are built with Webpack Encore. First, make sure
you have [yarn](https://yarnpkg.com/en/docs/install) installed. Then:

```
yarn
yarn encore dev
```

**Start the built-in web server**

You can use Nginx or Apache, but the built-in web server works
great:

```
php bin/console server:run
```

Now check out the site at `http://localhost:8000`

**For convenience**

If you are using PhpStorm you may install and enable
the [Symfony Plugin](https://plugins.jetbrains.com/idea/plugin/7219-symfony-plugin)
via the preferences which provides more auto-completion for Symfony projects.

Have fun!

## Ansible

**Ansible dependencies**

To manage third-party Ansible roles which are dependencies of our project we use
the requirements file. To install all the dependencies use:

```bash
$ ansible-galaxy install -r ansible/requirements.yml
```

**Ansible Vault**

We use Ansible Vault to hold all the sensitive data private. To edit the vault, execute:

```bash
$ ansible-vault edit ./ansible/vars/vault.yml
```

And enter the correct password. The password from our vault is `beefpass`.
For your own vaults you would probably use much stronger passwords and keep them secret.

**Create EC2 instance**

You can create a new AWS EC2 instance manually in Amazon dashboard or execute our
`aws.yml` playbook:

```bash
$ ansible-playbook ./ansible/aws.yml -i ./ansible/hosts.ini --ask-vault-pass
```

> You also need to set up your own Amazon credentials in Ansible Vault. See above how
to edit the Vault.

**Provision the server**

To provision your servers with Ansible, execute:

```bash
$ ansible-playbook ./ansible/playbook.yml -i ./ansible/hosts.ini -l aws --ask-vault-pass
```

Where `aws` is the host name which you want to provision. Also, you need to set up your
server's public IP address in `ansible/hosts.ini`.

**Deploy to production**

To deploy the project to production with Ansistrano, execute:

```bash
$ ansible-playbook ./ansible/deploy.yml -i ./ansible/hosts.ini --ask-vault-pass
```

## Have some Ideas or Feedback?

And as always, thanks so much for your support and letting us do what
we love!

If you have suggestions or questions, please feel free to
open an issue or message us.

<3 Your friends at KnpUniversity
