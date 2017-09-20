# Ansistrano Stages & Shared Files

When we deploy, our code *is* delivered to the server. But there are a few other
things we still need to do, like setting up database credentials in `parameters.yml`.

## The Ansistrano Deploy Stages

Scroll up to the top of the Ansistrano documentation and find the
[Main Workflow](https://github.com/ansistrano/deploy#main-workflow) link. When
we deploy, Ansistrano goes through five stages: Setup, Update Code, Symlink Shared,
Symlink, and Clean Up. The reason this is *really* interesting is that we can add
our own custom tasks *before* or *after* any of these stages. For example, we could
add a hook to run `composer install` or create `parameters.yml`.

The most important stages are "Update Code" - that's when our code is pulled down
from `git` and put into the new releases directory - and "Symlink", which is when
the `current` symlink is changed to that new directory. It's at *that* moment that
the site becomes live and traffic starts using the new code.

## The Shared Symlink

But look at the third stage: "Symlink Shared". Right now, each release is
*completely* separate from the others. We have 3 releases in 3 entirely isolated
directories: nothing is shared. But sometimes... you *do* want a file or directory
to be shared between deployments. For example, a log file: I want to have just *one*
log file that's used across deployments. I don't want each deployment to create
a new, empty log file.

In Ansistrano, this is done via the `shared/` directory. It's empty right now, but
we can configure it to hold certain *shared* paths. For example, eventually, we
will want the `var/logs` directory to be shared. We'll actually *do* this later,
but I want you to understand how it works now. When you configure `var/logs` to
be shared in Ansistrano, on the next deploy, this directory will be created inside
`shared/`. Then, every release will have a *symlink* to this shared directory.

That's what the "Symlink Shared" stage does: it creates all the shared symlinks in
the new release directory. That's important, because - after this stage - your code
should be fully functional.

## Creating parameters.yml

Google "Symfony deployment basics": you should find
[Symfony's deployment article](https://symfony.com/doc/current/deployment.html).
It lists the basic things you need to do when deploying a Symfony application, like
upload the code, install vendor dependencies and create your `app/config/parameters.yml`
file. Let's handle that next... via an Ansistrano hook!
