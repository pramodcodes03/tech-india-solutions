<?php

namespace App\Http\Controllers;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

class DocumentationController extends Controller
{
    public function show(?string $page = null)
    {
        $page = $page ?: 'introduction';
        $page = str_replace(['..', '\\'], '', $page); // security

        $mdPath = resource_path("docs/{$page}.md");
        if (! file_exists($mdPath)) {
            abort(404, 'Documentation page not found.');
        }

        $config = [];
        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new TableExtension);
        $converter = new MarkdownConverter($environment);

        $content = $converter->convert(file_get_contents($mdPath))->getContent();

        $pages = $this->getPages();

        return view('documentation.show', compact('content', 'page', 'pages'));
    }

    protected function getPages(): array
    {
        return [
            ['slug' => 'introduction', 'title' => 'Introduction'],
            ['slug' => 'getting-started', 'title' => 'Getting Started'],
            ['slug' => 'architecture', 'title' => 'Architecture Overview'],
            ['slug' => 'database-schema', 'title' => 'Database Schema'],
            ['slug' => 'module-users', 'title' => 'User Management'],
            ['slug' => 'module-roles', 'title' => 'Roles & Permissions'],
            ['slug' => 'module-customers', 'title' => 'Customer Management'],
            ['slug' => 'module-leads', 'title' => 'Lead Management'],
            ['slug' => 'module-quotations', 'title' => 'Quotation Management'],
            ['slug' => 'module-sales-orders', 'title' => 'Sales Order Management'],
            ['slug' => 'module-products', 'title' => 'Product Management'],
            ['slug' => 'module-inventory', 'title' => 'Inventory Management'],
            ['slug' => 'module-vendors', 'title' => 'Vendor & Purchase Management'],
            ['slug' => 'module-invoices', 'title' => 'Invoice Management'],
            ['slug' => 'module-payments', 'title' => 'Payment Management'],
            ['slug' => 'module-service', 'title' => 'Service Module'],
            ['slug' => 'module-dashboard', 'title' => 'Dashboard & Reports'],
            ['slug' => 'permissions-matrix', 'title' => 'Permissions Matrix'],
            ['slug' => 'routes-reference', 'title' => 'Routes Reference'],
            ['slug' => 'pdf-templates', 'title' => 'PDF Templates'],
            ['slug' => 'settings', 'title' => 'Settings & Customization'],
            ['slug' => 'testing', 'title' => 'Testing'],
            ['slug' => 'deployment', 'title' => 'Deployment'],
            ['slug' => 'troubleshooting', 'title' => 'Troubleshooting'],
            ['slug' => 'changelog', 'title' => 'Changelog'],
            ['slug' => 'contributing', 'title' => 'Contributing & License'],
        ];
    }
}
