# CircleCI: Auto-Deploy my Code!

This is not a tutorial about testing... but we couldn't resist! Our project actually
*does* have a small test suite. Find your local terminal. To run them, execute:

```terminal
./vendor/bin/simple-phpunit
```

This is a wrapper around PHPUnit. It will install some dependencies the first time
you try it and then... go tests go! They pass! Despite our best efforts, we haven't
broken anything.

So here is my lofty goal: I want to configure our project with continuous integration
on CircleCI and have CircleCI deploy *for* us, if the tests pass. Woh.

## CircleCI Setup

In your browser, go to ``http://circleci.com`` and login. I'll make sure I'm under
my own personal organization. Then go to projects and add a new project: our's is
called `ansistrano-deploy`.

To use CircleCI, we will need a `config.yml` file. Don't worry about that yet! Live
dangerously and just click "Start Building": this will activate a GitHub webhook
so that each code push will automatically create a new CircleCI build. The power!

Actually, this starts our first build! But since we *don't* have that `config.yml`
file yet, it's not useful.

## Creating .circleci/config.yml

Head back to your editor. If you downloaded the "start" code for the course, you
should have a `tutorial/` directory with a `circleci-config.yml` file inside. To
make CircleCI use this, create a new `.circleci` directory and paste it there:
but call it just `config.yml`.

We *will* talk about this file in a minute... but heck! Let's get crazy and just
try it first! Back on your local terminal, add that directory and commit:

```terminal-silent
git commit -m "CircleCI config"
```

Push wrecklessly to master! This should create a new build... there it is! It's
build #7... because - to be *totally* honest - I was doing a bit of practicing before
recording. I *usually* try to hide that... but I'm busted this time...

Anyways, click into the build. Ah, we're on some "Workflow" screen, and you can see
two different builds: `build_and_test` and `deploy`.

## Builds and Workflows in config.yml

Go back to `config.yml`. Under `jobs`, we have one called `build_and_test`: it sets
up our environment, installs composer, configures the database and... eventually,
runs the tests! But we also have a *second* job: `deploy`. The *whole* point of
this job is to install Ansible and get ready to run our Ansistrano deploy. We're
*not* actually doing this yet... but the environment should be ready.

The *real* magic is down below under `workflows`: the *one* workflow lists both
builds. *But*, thanks to the `requires` config, the `deploy` job will *only* run
if `build_and_test` is successful. That's *super* cool.

Back on CircleCI, that job *did* finish successfully, and `deploy` automatically
started. This *should* setup our Ansible-friendly environment... but it will *not*
actually deploy yet.

## CircleCI Environment Vars and the Vault Pass

It's time to fix that! In `config.yml`, under `deploy`, run the normal deploy command:
`ansible-playbook ansible/deploy.yml -i ansible/hosts.ini --ask-vault-pass`.

And in theory... that's all we need! But... do you see the problem? Yep: that `--ask-vault-pass`
option is *not* going to play well with CircleCI.

We need a different solution. Another option you can pass to Ansible is
`--vault-password-file` that points to a *file* that holds the password. That's
*better*... but how can we put the password in a file... without committing that
file to our repository?

The answer! Science! Well yes, but more specifically, environment variables!

Back in CircleCI, configure the project. Find "Environment Variables" and add a
new one called `ANSIBLE_VAULT_PASS` set to `beefpass`. Back in `config.yml`, before
deploying, we can `echo` that variable into a file: how about `./ansible/.vault-pass.txt`.

Use that on the next line: `--vault-password-file=` and then the path. To be extra
safe, delete it on the next line. And... I'll fix my ugly YAML.

## Setting Ansible Variablews

Ok, problem solved! Time to deploy, right!? Well... remember how we added that
prompt at the beginning of each deploy? Yep, that's going to break things too!
No worries: Ansible gives us a way to set variables from the *command* line. When
we do that, the prompt will *not* appear. How? Add a `-e` option with: `git_branch=master`.

## Disabling Host Key Checking

Ready to deploy... now!? Um... not so fast. Scroll up a little. Under the docker
image, we need to add one environment variable: `ANSIBLE_HOST_KEY_CHECKING` set to
no.

Whenever you SSH to a machine for the first time, SSH prompts you to verify the fingerprint
of that server. This disables that. If you have a highly sensitive environment,
you may need to look into actually *storing* the fingerprints to your servers instead
of just disabling this check.

Finally... I think we're ready! Go back to your local terminal, commit the changes,
and push!

## Adding ssh Keys

Go check it out. Ah, here is the new build: the `build_and_test` job starts off
immediately. Let's fast-forward. But watch, when it finishes.... yes! Visually,
you can see it activate the second job: `deploy`.

Inside this job, it sets up the environment first. When it starts running *our*
tasks... woh! It fails! Ah:

> Failed to connect to host via ssh... no such identity... permission denied

Of course! CircleCI is trying to SSH onto our servers, but it does not have access.
This works on our local machine because, when we deploy to the `aws` hosts, the
`group_vars/aws.yml` file is loaded. This tells Ansistrano to look for the SSH
key at `~/.ssh/KnpU-Tutorial.pem`. That path does *not* exist in CircleCI.

So... hmmm... We *could* leverage environment variables to create this file... but
great news! CircleCI gives us an easier way. Open up the key file and copy all of
its contents. Then, in CircleCI, configure the project and look for "SSH Permissions".
Add a new one: paste the key, but leave the host name empty. This will tell CircleCI
to use this key for all hosts.

We areready! In CircleCI, I'll just click rebuild. It skips straight to the `deploy`
job and starts setting up the environment. Then... yes! It's running our playbook!
OMG, go tell your co-workers! The machines are deploying the site to the other machines!
It takes a minute or two... but it *finishes*! CircleCI *just* deployed our site
automatically.

There's no visible difference, but we are setup!

Next, let's talk about some performance optimizations we should make to our deploy.
