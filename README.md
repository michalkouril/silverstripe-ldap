# Silverstripe LDAP module

[![CI](https://github.com/silverstripe/silverstripe-ldap/actions/workflows/ci.yml/badge.svg)](https://github.com/silverstripe/silverstripe-ldap/actions/workflows/ci.yml)
[![Silverstripe supported module](https://img.shields.io/badge/silverstripe-supported-0071C4.svg)](https://www.silverstripe.org/software/addons/silverstripe-commercially-supported-module-list/)

## Introduction

This Silverstripe module provides integration with LDAP (Lightweight Directory Access Protocol) servers. It comes with two major components:

* Synchronisation of Active Directory users and group memberships via LDAP(S)
* Active Directory authentication via LDAP binding

These components may be used in any combination, also alongside the default Silverstripe authentication scheme.

## Installation

```sh
composer require silverstripe/ldap
```

## Overview

This module will provide an LDAP authenticator for SilverStripe, which will authenticate via LDAPS against members in your AD server. The module comes with tasks to synchronise data between Silverstripe and AD, which can be run on a cron.

To synchronise further personal details, LDAP synchronisation feature can be used, also included in this module. This allows arbitrary fields to be synchronised - including binary fields such as photos. If relevant mappings have been configured in the CMS the module will also automatically maintain Silverstripe group memberships, which opens the way for an AD-centric authorisation.

**Note:** If you are looking for SSO with SAML, please see the [silverstripe-saml module](https://github.com/silverstripe/silverstripe-saml).

## Security

With appropriate configuration, this module provides a secure means of authentication and authorisation.

AD user synchronisation and authentication is hidden behind the backend (server to server communication), but must still use encrypted LDAP communication to prevent eavesdropping (either StartTLS or SSL - this is configurable). If the webserver and the AD server are hosted in different locations, a VPN could also be used to further encapsulate the traffic going over the public internet.

Note that the LDAP protocol does not communicate over HTTP. If this is what you're looking for, you may be interested in SAML instead.

## In-depth guides

* [Developer guide](docs/en/developer.md) - configure your Silverstripe site
* [CMS usage guide](docs/en/usage.md) - manage LDAP group mappings
* [Troubleshooting](docs/en/troubleshooting.md) - common problems

## Changelog

Please see the [GitHub releases](https://github.com/silverstripe/silverstripe-ldap/releases) for changes.
