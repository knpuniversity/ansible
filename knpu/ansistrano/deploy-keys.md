# Deploying Keys & Private Repos

I want to show you a quick trick. Right now, we're always deploying the `master`
branch:

[[[ code('05b07dddb8') ]]]

That probably make sense. But, sometimes, you might want to deploy a different branch,
like maybe a feature branch that you're deploying to a beta server. There are a few
ways to handle this, but one option is to leverage a native Ansible feature: `vars_prompt`:

[[[ code('b8a46089cb') ]]]

With this, we can just ask the user, well, *us*, which branch we want to deploy.
Whatever we type will become a new variable called `git_branch`. For the `prompt`,
say: `Enter a branch to deploy`. Default the value to `master` and set `private`
to `no`... so we can see what we type: this is not a sensitive password:

[[[ code('fa86be4f6c') ]]]

Down below, use the variable: `"{{ git_branch }}"`:

[[[ code('20550140fa') ]]]

This is nothing *super* amazing, but if it's useful, awesome! The downside is that
it will ask you a question at the beginning of *every* deploy. Try it:

```terminal
ansible-playbook -i ansible/hosts.ini ansible/deploy.yml
```

There's the prompt! I'll stop the deploy.

## How SSH Authentication Works

Now, to the *real* thing I want to talk about: deploy keys. Right now, the *only*
reason our deploy works is that... well, our repository is *public*! The server is
able to access my repo because *anyone* can. Go copy the `ssh` version of the URL
and use that for `ansistrano_git_repo` instead:

[[[ code('96c31f50de') ]]]

Now try the deploy:

```terminal-silent
ansible-playbook -i ansible/hosts.ini ansible/deploy.yml
```

It starts off good... but then... error! It says:

> Permission denied (public key). Could not read from remote repository

Woh! When you use the `ssh` protocol for Git, you authenticate with an `ssh` key.
Basically, you generate a private and public key on your machine and then *upload*
the public key to your GitHub account. Once you do that, each time you communicate
with GitHub, you send your public key so that GitHub knows who you are and what
repositories you have access to. And also, behind the scenes, the private key on
your local machine is used to *prove* that you own that public key. Actually, none
of this is special to `git`, this is how SSH key-based authentication works anywhere.

Even though our repository is still *public*, you need *some* valid SSH key pair
in order to authenticate... and our server has nothing. That's why this is failing.
To fix this, we'll use a *deploy* key... which will allow our server to clone the
repository, whether it's public or private.

## Creating a Deploy Key

Here's how it works. First, locally, generate a new public and private key:
`ssh-keygen -t rsa -b 4096 -C`, your email address - `ryan@knpuniversity.com` then
`-f ansible/id_rsa`:

```terminal-silent
ssh-keygen -t rsa -b 4096 -C "ryan@knpuniversity.com" -f ansible/id_rsa
```

You can use a pass phrase if you want, but I won't. When this is done, we have two
new fancy files inside the `ansible/` directory: `id_rsa` - the private key - and
`id_rsa.pub` the key to your local pub. I mean, the public key.

Back on GitHub, on the repository, click "Settings" and then "Deploy Keys". Add
a deploy key and give it a name that'll help you remember why you added it. Go find
the public key - `id_rsa.pub` - copy it, and paste it here. Add that key!

Boom! The nice thing is that this will only give our server *read* access to the
repository.

## Configuring Ansistrano to use the private key

But this is only one half of the equation: we need to tell Ansistrano to *use*
the private key - `id_rsa` - when it communicates with GitHub.

But that's why we use Ansistrano! They already thought about this, and exposed
two variables to help: `ansistrano_git_identity_key_path` and
`ansistrano_git_identity_key_remote_path`. Basically, we need to store the private
key *somewhere*: it can live on our local machine where we *execute* Ansible - that's
the first variable - or you can put it on the server and use the second variable.

Let's use the first option and store the key locally. Copy the first variable:
`ansistrano_git_identity_key_path`. Set it to `{{ playbook_dir }}/id_rsa`:

[[[ code('3bb581f749') ]]]

`playbook_dir` is an Ansible variable, and it points to the `ansible/` directory:
the directory that holds the playbook file. As *soon* as we do this, Ansistrano will
use this private key when it talks to GitHub. And because we've added its partner
public key as a deploy key to the repo, it *will* have access!

## Storing the Private Key

Of course, this means that you *need* to make sure that the `id_rsa` file exists.
You can either do this manually somehow... or you can do something a bit more controversial:
commit it to your repository. I'll do that: add the file, then commit: "adding private
deploy identity key to repo".

```terminal-silent
git add ansible/id_rsa
git commit -m "adding private deploy identity key to repo"
```

This is controversial because I *just* committed a private key to my repository!
That's like committing a password! Why did I do this? Mostly, simplicity! Thanks
to this, the private key will *always* exist.

How bad of a security issue is this? Well, this key only gives you read-only access
to the repository. And, if you were already able to download the code... then you
were *already* able to access it. This key doesn't give you any *new* access. But,
if you *remove* someone from your GitHub repository... they could still use this
key to continue accessing it in the future. *That's* the security risk.

An alternative would be to store the private key on S3, then use the S3 Ansible
module to download that onto the server during deployment. Make the decision that's
best for you.

Whatever you choose, the point is: the variable is set to a local path on our filesystem
where the private key lives. This means... we can deploy again! Try it:

```terminal-silent
ansible-playbook -i ansible/hosts.ini ansible/deploy.yml
```

It's working... working... and ... it's done! Scroll up a little. Cool! It ran a
few new tasks: "Ensure Git deployment key is up to date" and then later
"shred Git deployment key". It uses the key, but then removes it from the server
after. Nice!

The server can now pull down our code... even if the repository is private.

Next! Deployment is *not* working yet: we still need to setup `parameters.yml`
and do a few other things.
