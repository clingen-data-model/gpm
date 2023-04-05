# Setup some general things (not per-project, should just need to do once)

## DOCKER_USER variable

In order to run containers as the same uid/gid as the current user (so docker local mounts
have the appropriate permissions), we will use a convention that DOCKER_USER will be set to
`${UID}:${GID}`. UID and GID are shell variables, not environment variables (for reasons I
don't understand), so we have to set this up in a `.profile`, `.zprofile` or the likes.
I just put `export DOCKER_USER=${UID}:${GID}` at the beginning of my `~/.zprofile`.

## Case sensitivity for src

The current default filesystem on macos can be set to be case sensitive or not. By default
it is case insensitive, and apparently some software relies on this (like some Adobe stuff,
maybe). Yet case sensitivity is expected for other software (for instance mysql...)

MacOs allows for creation of "subvolumes" that are case sensitive. I put one of these
under `$HOME/src` and put all of my source code there. For example:

```bash
sudo diskutil apfs addVolume disk1 APFSX src -mountpoint "$HOME/src"
sudo chown $DOCKER_USER "$HOME/src"
```

# Install [homebrew](brew.sh)

```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

(echo; echo 'eval "$(/usr/local/bin/brew shellenv)"') >> "$HOME/.zprofile"
    eval "$(/usr/local/bin/brew shellenv)"
```

# Install brew packages

```bash
# dev environment
brew install colima docker docker-compose
# other useful tools
brew install git git-crypt kubernetes-cli jq
# some personal preferences (bpow)
brew install iterm2 neovim
```

# Initial colima setup

It's important to set the `vm-type` and `mount-type` on iniital run of colima so that
the more-efficient virtiofs mounting method is used (also avoids some race conditions
that were present in sshfs-based mounting). Subsequent runs of `colima start` (e.g.,
after restarting) should use the settings established here.

```bash
colima start --vm-type vz --mount-type virtiofs --cpu 6 --memory 6 --disk 90
```

