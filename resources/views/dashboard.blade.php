@extends('adminlte::page')

@section('title', 'Dashboard - BizNexus')

@section('content_header')
    <h1 class="m-0 text-dark">Dashboard</h1>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>150</h3>
                    <p>New Orders</p>
                </div>
                <div class="icon"><i class="fas fa-shopping-cart"></i></div>
                <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>53<sup style="font-size: 20px">%</sup></h3>
                    <p>Conversion Rate</p>
                </div>
                <div class="icon"><i class="fas fa-chart-line"></i></div>
                <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>44</h3>
                    <p>Pending Invoices</p>
                </div>
                <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>12</h3>
                    <p>Open Tickets</p>
                </div>
                <div class="icon"><i class="fas fa-life-ring"></i></div>
                <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Activity</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><strong>Admin</strong> created a new invoice <span class="float-right text-muted text-sm">5 mins ago</span></li>
                        <li class="list-group-item"><strong>Jane</strong> approved order <strong>#1042</strong> <span class="float-right text-muted text-sm">23 mins ago</span></li>
                        <li class="list-group-item"><strong>System</strong> sent 12 notifications <span class="float-right text-muted text-sm">1 hour ago</span></li>
                        <li class="list-group-item"><strong>Mark</strong> closed ticket <strong>#312</strong> <span class="float-right text-muted text-sm">2 hours ago</span></li>
                        <li class="list-group-item"><strong>Lisa</strong> registered a new customer <span class="float-right text-muted text-sm">3 hours ago</span></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Team Members</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="users-list clearfix">
                        <li><img src="https://ui-avatars.com/api/?name=Alice+Smith&background=0D8ABC&color=fff" alt="Alice" width="64"><span class="users-list-name">Alice</span><span class="users-list-date">Today</span></li>
                        <li><img src="https://ui-avatars.com/api/?name=Bob+Lee&background=28a745&color=fff" alt="Bob" width="64"><span class="users-list-name">Bob</span><span class="users-list-date">Yesterday</span></li>
                        <li><img src="https://ui-avatars.com/api/?name=Carla+Diaz&background=dc3545&color=fff" alt="Carla" width="64"><span class="users-list-name">Carla</span><span class="users-list-date">12 Jan</span></li>
                        <li><img src="https://ui-avatars.com/api/?name=David+Kim&background=ffc107&color=fff" alt="David" width="64"><span class="users-list-name">David</span><span class="users-list-date">10 Jan</span></li>
                    </ul>
                </div>
                <div class="card-footer text-center"><a href="#">View All Members</a></div>
            </div>
        </div>
    </div>
@endsection