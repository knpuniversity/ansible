# Deploy with git

Our *first* deployment task is simple: we need to get our code to the server!
By default, Ansistrano does that via `rsync`... but it has a *bunch* of options!
Check out the `ansistrano_deploy_via` variable. Beyond `rsync`, you can use `copy`,
`git`, `svn`, `s3` - if you want to fetch your code from an S3 bucket - and `download`.
We're going to use the `git` strategy! And most of the rest of the variables in the
docs are specific to your deploy strategy.

## Setting up the git Repo

Ok, so we're going to deploy via `git`... which means... well, we should probably
create a git repository! First, I'll commit everything locally. You may even need
to initialize your git repository with `git init`. I already have a repo, so I'll
just commit:

```terminal-silent
git add .
git commit -m "I'm king of the world!"
```

Perfect! Next, we need to host our Git repository somewhere. It doesn't matter where,
but I'll use GitHub. Create a brand new repository! Woo! I'm making this *public*,
but we *will* talk soon about how to deploy *private* repositories.

Copy the 2 lines to add the remote and push our code. Then, in your terminal,
paste them:

```terminal
git remote add origin git@github.com:weaverryan/ansistrano-deploy.git
git push -u origin master
```

Progress!

Back on GitHub, refresh! There is our beautiful code!

## Configuring the Deploy

Back in Ansistrano land, the first thing we need to do is configure that
`ansistrano_deploy_via` variable. Set it to `git`:

[[[ code('2686f1617a') ]]]

For the Git-specific variables, we need to configure two: the URL to the repo
and what *branch* to deploy. Copy `ansistrano_git_repo` first and paste it.
For the URL, go back to GitHub and click on "Clone or download". For now, use
the `https` version of the URL. We're going to change this in a few minutes - but
this makes life simpler to start:

[[[ code('c39865f0ba') ]]]

Now copy the last variable: `ansistrano_git_branch`. We don't *really* need to set
this... because it defaults to `master`. But let's set it anyways:

[[[ code('4349f14c7a') ]]]

Moment of truth! Go back to your terminal and run the playbook again:

```terminal
ansible-playbook -i ansible/hosts.ini ansible/deploy.yml
```

This time, we see some Git-related tasks. So that's probably good! And it finishes
without any errors.

Let's go see what it did! I'll move back to my terminal that's SSH'ed onto the server.
Move *out* of the `current/` directory. That's important: the `current` symlink
*did* change, but until you move out of it, you're still looking at the *old* release
directory.

Ok cool! There are two things in `releases/`, and the symlink points to the new one.
Move back into `current/`. And... there's our project! Our code is deployed! Yea,
we *are* missing some things, like `parameters.yml`, but we'll get there. For
now, celebrate!
