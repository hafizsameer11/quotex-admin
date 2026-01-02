
            <!-- ========== Left Sidebar Start ========== -->
            <div class="left side-menu">
                <button type="button" class="button-menu-mobile button-menu-mobile-topbar open-left waves-effect">
                    <i class="ion-close"></i>
                </button>

                <!-- LOGO -->
                <div class="topbar-left">
                    <div class="text-center">
                        <!--<a href="index.html" class="logo"><i class="mdi mdi-assistant"></i>Zoter</a>-->
                        <a href="{{ route('dashboard') }}" class="logo">
                            {{-- <h2 class=" text-white">MineProfit</h2> --}}
                        </a>
                    </div>
                </div>

                <div class="sidebar-inner niceScrollleft">

                    <div id="sidebar-menu">
                        <ul>
                           

                            <li>
                                <a href="{{ route('dashboard') }}" class="waves-effect">
                                    <i class="mdi mdi-airplay"></i>
                                    <span> Dashboard </span>
                                </a>
                            </li>

                            {{-- <li class="has_sub">
                                <a href="javascript:void(0);" class="waves-effect"><i class="mdi mdi-layers"></i> <span> Advanced UI </span> <span class="float-right"><i class="mdi mdi-chevron-right"></i></span></a>
                                <ul class="list-unstyled">
                                    <li><a href="advanced-highlight.html">Highlight</a></li>
                                    <li><a href="advanced-rating.html">Rating</a></li>
                                    <li><a href="advanced-alertify.html">Alertify</a></li>
                                    <li><a href="advanced-rangeslider.html">Range Slider</a></li>
                                </ul>
                            </li> --}}

                            <li>
                                <a href="{{ route('users') }}" class="waves-effect"><i class="mdi mdi-layers"></i><span> Total Users </span></a>
                            </li>
                            <li>
                                <a href="{{ route('deposits') }}" class="waves-effect"><i class="mdi mdi-layers"></i><span> Total Deposits </span></a>
                            </li>
                            <li>
                                <a href="{{ route('withdrawals') }}" class="waves-effect"><i class="mdi mdi-layers"></i><span> Total Withdrawals </span></a>
                            </li>
                            <li>
                                <a href="{{ route('plans') }}" class="waves-effect"><i class="mdi mdi-layers"></i><span> Investment Plan </span></a>
                            </li>
                            <li>
                                <a href="{{ route('referrals') }}" class="waves-effect"><i class="mdi mdi-layers"></i><span> Referrals </span></a>
                            </li>
                            <li>
                                <a href="{{ route('transactions') }}" class="waves-effect"><i class="mdi mdi-layers"></i><span> Transactions </span></a>
                            </li>
                            <li>
                                <a href="{{ route('chains.index') }}" class="waves-effect"><i class="mdi mdi-layers"></i><span> Chain </span></a>
                            </li>
                            <li>
                                <a href="{{ route('loyalty.index') }}" class="waves-effect"><i class="mdi mdi-star"></i><span> Loyalty Management </span></a>
                            </li>
                            <li>
                                <a href="{{ route('kyc.index') }}" class="waves-effect"><i class="mdi mdi-star"></i><span> KYC Documnets </span></a>
                            </li>
                            <li>
                                <a href="{{ route('news.index') }}" class="waves-effect"><i class="mdi mdi-star"></i><span> News </span></a>
                            </li>
                            <li>
                                <a href="{{ route('investment-control.index') }}" class="waves-effect"><i class="mdi mdi-chart-line"></i><span> Investment Control </span></a>
                            </li>
                            <li>
                                <a href="{{ route('mining-control.index') }}" class="waves-effect"><i class="mdi mdi-cog"></i><span> Mining Control </span></a>
                            </li>
                            <li>
                                <a href="{{ route('daily-mining-codes.index') }}" class="waves-effect"><i class="mdi mdi-key-variant"></i><span> Daily Mining Codes </span></a>
                            </li>
                            <li>
                                <a href="{{ route('user-extra-codes.index') }}" class="waves-effect"><i class="mdi mdi-account-key"></i><span> User Extra Codes </span></a>
                            </li>


                            {{-- <li class="has_sub">
                                <a href="javascript:void(0);" class="waves-effect"><i class="mdi mdi-bullseye"></i> <span> UI Elements </span> <span class="float-right"><i class="mdi mdi-chevron-right"></i></span></a>
                                <ul class="list-unstyled">
                                    <li><a href="ui-buttons.html">Buttons</a></li>
                                    <li><a href="ui-cards.html">Cards</a></li>
                                    <li><a href="ui-tabs-accordions.html">Tabs &amp; Accordions</a></li>
                                    <li><a href="ui-modals.html">Modals</a></li>
                                    <li><a href="ui-images.html">Images</a></li>
                                    <li><a href="ui-alerts.html">Alerts</a></li>
                                    <li><a href="ui-progressbars.html">Progress Bars</a></li>
                                    <li><a href="ui-dropdowns.html">Dropdowns</a></li>
                                    <li><a href="ui-lightbox.html">Lightbox</a></li>
                                    <li><a href="ui-navs.html">Navs</a></li>
                                    <li><a href="ui-pagination.html">Pagination</a></li>
                                    <li><a href="ui-popover-tooltips.html">Popover & Tooltips</a></li>
                                    <li><a href="ui-badge.html">Badge</a></li>
                                    <li><a href="ui-carousel.html">Carousel</a></li>
                                    <li><a href="ui-video.html">Video</a></li>
                                    <li><a href="ui-typography.html">Typography</a></li>
                                    <li><a href="ui-sweet-alert.html">Sweet-Alert</a></li>
                                    <li><a href="ui-grid.html">Grid</a></li>
                                </ul>
                            </li> --}}

                            {{-- <li class="has_sub">
                                <a href="javascript:void(0);" class="waves-effect"><i class="mdi mdi-clipboard-outline"></i><span> Forms </span> <span class="badge badge-pill badge-info float-right">8</span></a>
                                <ul class="list-unstyled">
                                    <li><a href="form-elements.html">Form Elements</a></li>
                                    <li><a href="form-validation.html">Form Validation</a></li>
                                    <li><a href="form-advanced.html">Form Advanced</a></li>
                                    <li><a href="form-editors.html">Form Editors</a></li>
                                    <li><a href="form-uploads.html">Form File Upload</a></li>
                                    <li><a href="form-mask.html">Form Mask</a></li>
                                    <li><a href="form-summernote.html">Summernote</a></li>
                                    <li><a href="form-xeditable.html">Form Xeditable</a></li>
                                </ul>
                            </li> --}}

                            


                        </ul>
                    </div>
                    <div class="clearfix"></div>
                </div> <!-- end sidebarinner -->
            </div>
            <!-- Left Sidebar End -->
        <!-- Start right Content here -->

    <div class="content-page">
          <!-- Start content -->
        <div class="content">