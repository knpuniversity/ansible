# Composer & Cache Permissions

Look back at the Symfony Deployment article: we *now* have a parameters file! Next,
we need to run `composer install` - which was the *original* reason the site didn't
work - and then warup the Symfony cache. We're *really* close to a functional site.
We won't need to dump the Assetic assets - we're not using Assetic - but we *will*
need to do some asset processing later.

## Running composer install

Let's add a new task to run `composer` install. In the hook file, add a new task:
"Install composer deps". Use the `composer` module and tell it to run the `install`
command. We also need to set the `working_dir`: use `{{ ansistrano_release_path.stdout}}`

Perfect! One gotcha with the `composer` module is that, by default, it runs
`composer install --no-dev`. That means that your `devDependencies` in `composer.json`
will *not* be downloaded! For production, this is a good thing: it'll give you a
small performance boost. Just make sure you're not relying on anything in those
libraries!

Also, in Symfony 3, if you use `--no-dev`, then some of the `post-install` Composer
tasks will fail, because they *need* those dev dependencies. To fix that, you need
to set an environment variable: `SYMFONY_ENV=prod`.

No problem! In `deploy.yml`, add a new key called `environment`. And below,
`SYMFONY_ENV` set to `prod`. Thanks to this, the Composer post-install tasks won't
explode.

Oh, and for this to work, composer needs to already be installed on your server.
We did that in our provision playbook.

## Clearing & Warming Cache

Before we try all this, let's tackle one last thing: clearing the Symfony cache...
which basically means running two console commands.

To make this easier, in `deploy.yml`, add a new variable: `release_console_path`.
Copy the `ansistrano_release_path.stdout` variable and paste it here:
`{{ ansistrano_release_path.stdout }}/bin/console`.

Cool! Back in the hook file, add a new task to clear the cache. Use the `command`
module to simply say
`{{ release_console_path }} cache:clear --no-warmup --env=prod`. That's basically
the command that you see inside the documentation.

If you're not familiar with it, the `--no-warmup` flag, it's important. In Symfony
4, instead of running `cache:clear` and expecting it to clear your cache *and*
warm up your cache, `cache:clear` will *only* clear your cache. Then, you should
use `cache:warmup` separately to warm it up. By passing `--no-warmup`, we're imitating
the Symfony 4 behavior so that we're ready.

Add the second task: "Warm up the Cache". Copy the command, but change it to just
`cache:warmup --env-prod`. Now, technically, since the `cache/` directory is not
shared between deploys, we don't *really* need to run `cache:clear`: it will already
be empty! But, I'll keep it here.

Ok! Phew! I think we've done everything, let's deploy! Find your local terminal
and run the playbook:

```terminal-silent
ansible-playbook -i ansible/hosts.ini ansible/deploy.yml
```

Use `beefpass` as the vault password and deploy to `master`. Then... wait impatiently!
Someone fast forward this please!

Yes! No errors! On the server, move out of `current` and then back in. Check it
out! Our `vendor/` directory is full of files!

## Fixing the File Permissions

Moment of truth: try the site again: mootube.example.com. Bah! It *still* doesn't
work. Let's find out why. On the server, tail the log file:

```terminal
sudo tail /var/log/nginx/mootube.example.com_error.log
```

Ooooh:

> PHP Fatal error: The stream or file "var/logs/prod.log" could not be opened

Of course! We have permissions problems on the `var/` directory! Fixing this is
actually a *very* interesting topic. There is an easy way to fix this... and a more
complex, but more secure way.

For now, let's use the simple way: I *really* want our app to work! Add a new task: 
"Setup directory permissions for var". Use the `file` module. But, quickly, go back
to `deploy.yml` and make another variable: `release_var_path` set to the same path
`{{ ansistrano_release_path.stdout }}/var`.

Now, back in `after-symlink-shared.yml`, set the path to `{{ release_var_path }}`,
`state` to `directory`, `mode` to `0777` and `recurse: true`. On deploy, this will
make sure that directory exists and is set to 777. That's not the *best* option
for security... but it should get things working!

Deploy one more time:

```terminal-silent
ansible-playbook -i ansible/hosts.ini ansible/deploy.yml
```

Type `beefpass`, deploy to master... and watch the magic. I can see the new directory
permissions task... and it finished.

Refresh the site! Eureka! Yea, it's *still* a 500 error, but this comes from Symfony!
Symfony *is* running! Change the URL to `http://mootube.example.com/about`. It
works! It's *super* ugly - we need to do some work with our assets - but it *does*
work. The homepage is broken because our database isn't setup. But this static page
proves our deploy is working! Victory!

Now, let's smooth out the missing details... like the unsecure permissions, the
database and our assets... because this site is *ugly*!
