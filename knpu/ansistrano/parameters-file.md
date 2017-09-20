# Deploy Hooks & parameters.yml

Go look inside the `current` directory on your server. Guess what? There is *no*
`parameters.yml` file yet! That's no surprise! This is *not* committed to git, so
it's not downloaded from git.

## Adding a Hook

Let's add this by adding a *hook* into Ansistrano. How? To add a hook before or after
any of these stages, you can override a *variable*... and you can see those variables
back down in the variable reference. Ah, yes! Choose the correct variable for the
hook you want, set it to a new file, and start adding tasks!

Copy the `ansistrano_after_symlink_shared_tasks_file` variable: we're going to add
a hook *after* the "Symlink Shared" stage. Why there? Well, this is *after* any
shared symlinks have been created... but *just* before the site becomes live. Said
differently, at this stage, our site is functional... but it's not live yet. It's
a great hook spot.

Inside `deploy.yml`, paste that variable and set it to a new file:
`{{ playbook_dir }}/deploy/after-symlink-shared.yml`. Copy that filename and, inside
`ansible/`, create that `deploy/` directory and a new file: `after-symlink-shared.yml`.

## Creating parameters.yml

Ok, next question: how should we create `parameters.yml`? There are two options.
The easier, but less automatic option is to configure `app/config/parameters.yml`
as a *shared* file. If we did that, on the next deploy, Ansistrano would create an
`app/config/parameters.yml` file inside `shared/`. We could then SSH onto the server
manually and configure that file. As *soon* as we did that, all future deploys would
use this shared file. We'll cover shared files more later.

But... this requires manual work... and each time the file needs to change... you
need to *remember* to update it... manually. I remember nothing!

The second option is to create `parameters.yml` via Ansible. Inside the `ansible/`
directory, create a new `templates/` directory. Next, copy `app/config/parameters.yml.dist`
from your project into here. Big picture, here's the plan: we will use the Ansible
`template` module, to render variables inside this file, and deploy it to the
server. But... to start, we're going to just use these hardcoded values.

Back in `after-symlink-shared.yml`, add an new task:
"Setup infrastructure-related parameters". Use the `template` module to, for now,
copy `{{ playbook_dir }}/templates/parameters.yml.dist` into the new release directory.

But... um... how do we know what the name of the new release directory is? I mean,
it's always changing!? And this hook is *before* the `current` symlink is created,
so we can't use that.

Go back to the Ansistrano docs and search for `ansistrano_release_path`. Yes! Near
the bottom, there's a section called "Variables in custom tasks". Ansistrano gives
us a few *really* helpful variables... and this explains them.

And yes! The first variable is *exactly* what we need. But don't forget about the
others: you may need them someday.

Back in `after-symlink-shared.yml`, set the destination to
`{{ ansistrano_release_path.stdout }}/app/config/parameters.yml`.

We're not *customizing* anything in this file yet... but this should be enough to
get it onto the server. Let's try it: deploy, deploy!

```terminal-silent
ansible-playbook -i ansible/hosts.ini ansible/deploy.yml
```

It takes a few moments... but it worked! On your server, move back into the `current`
directory. Yes! *Now* we have a `parameters.yml` file.

Cool! But... of course... it's still full of hardcoded info. Next, we need to fill
this file with our *real*, production config. And we need to do that securely.
