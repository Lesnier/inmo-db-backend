@extends('voyager::master')

@section('page_title', 'System Documentation')

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-documentation"></i> System Documentation v1.0
    </h1>
@stop

@section('content')
    <div class="page-content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-body">
                        <ul class="nav nav-tabs">
                            <li class="active"><a data-toggle="tab" href="#overview">Overview & Concepts</a></li>
                            <li><a data-toggle="tab" href="#modules">Modules & Global</a></li>
                            <li><a data-toggle="tab" href="#integration">Real Estate + CRM</a></li>
                            <li><a data-toggle="tab" href="#associations">Associations Model</a></li>
                            <li><a data-toggle="tab" href="#datatypes">Data Types Library</a></li>
                        </ul>

                        <div class="tab-content">
                            <div id="overview" class="tab-pane fade in active">
                                <h3>Project Concept</h3>
                                <p>This project is a comprehensive Real Estate Platform integrated with a robust CRM system. It unifies property management, lead generation, and sales/support pipelines into a single ecosystem.</p>
                                
                                <h4>Key Architecture</h4>
                                <ul>
                                    <li><strong>Backend:</strong> Laravel 10 + Voyager (Admin Panel)</li>
                                    <li><strong>Database:</strong> MySQL with Polymorphic Relationships</li>
                                    <li><strong>API:</strong> RESTful API documented via Swagger</li>
                                </ul>

                                <h4>API Documentation</h4>
                                <p>
                                    Interactive API documentation is available at: 
                                    <a href="{{ url('/api/documentation') }}" target="_blank" class="btn btn-primary btn-sm">
                                        <i class="voyager-lightning"></i> Open Swagger UI
                                    </a>
                                </p>
                            </div>

                            <div id="modules" class="tab-pane fade">
                                <h3>Global Sections & Modules</h3>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h4>Real Estate Core</h4>
                                        <ul>
                                            <li><strong>Properties:</strong> Listings with extensive attributes, media, and location.</li>
                                            <li><strong>Buildings/Projects:</strong> Multi-unit developments.</li>
                                            <li><strong>Search Engine:</strong> Advanced filtering by location, price, features.</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h4>CRM Module</h4>
                                        <ul>
                                            <li><strong>Contacts:</strong> Unified view of Leads, Clients, Agents.</li>
                                            <li><strong>Deals:</strong> Sales opportunities managed in pipelines.</li>
                                            <li><strong>Tickets:</strong> Support requests or internal tasks in pipelines.</li>
                                            <li><strong>Timeline:</strong> aggregated view of activities, emails, and meetings.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div id="integration" class="tab-pane fade">
                                <h3>Real Estate + CRM Integration</h3>
                                <p>The power of this platform lies in the seamless integration between property listings and the CRM.</p>
                                
                                <div class="alert alert-info">
                                    <strong>Workflow Example:</strong>
                                    <ol>
                                        <li>A visitor searches for properties (Real Estate Core).</li>
                                        <li>They request info on a specific Property (Listing).</li>
                                        <li>A <strong>Contact</strong> is created/updated in the CRM.</li>
                                        <li>A <strong>Deal</strong> is automatically created in the Sales Pipeline.</li>
                                        <li>The Deal is <strong>Associated</strong> with the Property and the Contact.</li>
                                        <li>Agents track the Deal progress via the Kanban board.</li>
                                    </ol>
                                </div>
                            </div>

                            <div id="associations" class="tab-pane fade">
                                <h3>Universal Association Model (N-to-N)</h3>
                                <p>We use a flexible `inmo_associations` table that allows linking any entity to any other entity without creating specific pivot tables for every pair.</p>
                                
                                <pre>
Table: inmo_associations
- id
- model_a_type (e.g., App\Models\Contact)
- model_a_id
- model_b_type (e.g., App\Models\Deal)
- model_b_id
- type (e.g., 'primary', 'participant')
                                </pre>

                                <p>This allows us to link:</p>
                                <ul>
                                    <li>Contacts to Deals</li>
                                    <li>Contacts to Tickets</li>
                                    <li>Companies to Contacts</li>
                                    <li>Deals to Properties</li>
                                </ul>
                            </div>

                            <div id="datatypes" class="tab-pane fade">
                                <h3>Data Types & Libraries</h3>
                                <p>The system uses standard libraries to ensure data consistency.</p>
                                
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Module</th>
                                            <th>Key Enums/Types</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>Pipelines</strong></td>
                                            <td>Entity Types: 'deal', 'ticket'</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Contacts</strong></td>
                                            <td>Lifecycle Stages: 'subscriber', 'lead', 'mql', 'sql', 'customer', 'evangelist'</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Activities</strong></td>
                                            <td>Types: 'email', 'call', 'meeting', 'note', 'sms'</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .tab-content { padding: 20px 0; }
        .nav-tabs > li > a { font-weight: bold; }
    </style>
@stop
