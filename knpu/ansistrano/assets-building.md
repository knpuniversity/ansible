# Building Encore/Webpack Assets

Our site is at *least* functional. Well any page that doesn't use the *database*
is functional... like the about page. But it *is* super ugly. Oof. Why? It's
simple! Our CSS file - `build/styles.css` is missing! Oooh, a mystery!

Our Mootube asset setup is pretty awesome: instead of having simple CSS files,
we're getting sassy with Sass! And to build that into CSS, we're using an awesome
library called Webpack Encore. Actually, we have a tutorial on Webpack... so go watch
that if you're curious!

Basically, Webpack Encore is a Node executable: you run it from the command
line, and it - in our simple app - transforms that Sass file into `build/styles.css`.

The reason `styles.css` wasn't deployed is that its directory - `/web/build` -
is *ignored* in `.gitignore`! We need to do more work to deploy it.

## Running assets:install

But before we get too far into that, there's one other *little* command that you
often run during deployment:

```terminal
bin/console assets:install --symlink
```

Actually, when you run `composer install`, this command is run automatically...
so you may not even realize it's happening. And for deploy... well... you may or
may *not* even need it! Here's the deal: sometimes, a bundle - usually a third-party
bundle - will come with some CSS, JS or other public assets. Those files, of course,
live in the `vendor/` directory... which is a *problem*... because it means they're
not publicly accessible. To *make* them public, we run this command. For each bundle
that has public assets, it creates a symlink in the `web/bundles` directory.

For our app... yea... we don't have *any* bundles that do this! But, let's run
that command on deploy to be safe. Open up `after-symlnk-shared.yml`. Let's add
a new task called "Install bundle assets".

Set this to run a command, and use the `release_console_path` variable that we
setup in `deploy.yml`. Add `assets:install --symlink` and then `--env=prod`.

That's important: we need to run all of our console commands with `--env=prod`.
Running commands in `dev` mode won't even work, because our `require-dev` composer
dependencies were never installed.

Perfect!

## Installing Node Dependencies

Let's move on to the *real* task: building our assets on production. I'm not going
to talk about Encore too much, but building the assets is a two-step process.

First, run:

```terminal
yarn install
```

to download all of your Node dependencies. Basically, this reads `package.json` and
downloads that stuff into a `node_modules/` directory. It's basically like Composer
for Node.

Step 2 is to run Encore and build your assets. First, I'll clear out the build
directory:

```terminal
rm -rf web/build/*
```

Then, run:

```terminal
./node_modules/.bin/encore production
```

Cool! Check it out! Yes! We still have `styles.css`, and it's beautifully minified.

## Where to Build the Assets?

So can we do this during deploy? Well... we have a few options. First, you *could*
decide to run these commands locally and commit those files to your repository. That
makes deploying easy... but you need to remember to run this command before each
deploy. And committing built files to your repo is a bummer. This is an easy, but
hacky way to handle things.

But... we *do* need to run these commands *somewhere*. The most obvious solution
is actually to run these commands on your production server *during* deployment.
This *is* what we're going to do... but it's *also* a bummer. It means that we will
need to install Node on our production server... *just* to build these assets.

So, if you really don't like the idea of running these commands on production, you
have a few other options. If you already have a build system of some sort, you could
build your assets on that machine, upload them to S3 or something similar, then download
them from during deployment. Or, skip the downloading part, and update your script
and link tags to point to S3 or some CDN.

A second option is to add a  *play* to your deploy playbook that would first build
those assets *locally*, before using the `copy` module to move them up to production.

## Installing Node & Yarn

We're going to build the assets on production. This is not the best solution, but
it's easy, and works great in most situations. The only big requirement is that
we need to install Node and yarn there. I'm going to open up my *provision* playbook:
`playbook.yml`. Then, near the bottom, paste some tasks that do this.

If you're not using Ansible to provision your server, just install Node and yarn
however you want. Let's get this running!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini --ask-vault-pass -l aws
```

Use `beefpass` for the password. Go go go!

## Build the Assets on Deploy

While that's doing its magic, open `deploy.yml`. We need to run 2 commands on
deploy. Add a new task called "Install Node dependencies". Use `command` set
to `yarn install`. Oh, and make sure this runs *in* our project directory: add `args`
then `chdir` set to our handy `ansistrano_release_path.stdout`.

Copy that to make the second task: `Install Webpack Encore assets`. For the command,
use `./node_modules/.bin/encore production`.

Ok, that's pretty easy! The annoying part is just *needing* to setup Node on your
production server. Let's go check on the provision!

Oh boy... let's fast forward... ding!

Thanks to that, we *should* have Node and yarn up on the server. Let's deploy!
Same command, but with `deploy.yml`, and we don't need the `-l aws`... this already
*only* runs against the `aws` host.

```terminal-deploy
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini --ask-vault-pass
```

Use `beefpass`, deploy master and... hold your breath! Most of this will be the
same... but watch those two new tasks. The Node dependencies will take some time
to install at first.

Ok done! No errors. So... let's try it! Refresh! We have CSS! Yes!

We are finally ready to fix the database... and our poor homepage.
