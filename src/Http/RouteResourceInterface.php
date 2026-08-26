<?php

declare(strict_types=1);

namespace Webware\Acl\Http;

use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Webware\Acl\RoleProviderInterface;

/**
 * @api
 */
interface RouteResourceInterface extends ResourceInterface, RoleProviderInterface {}
