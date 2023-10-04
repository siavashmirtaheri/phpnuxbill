{include file="sections/header.tpl"}
<!-- routers-add -->

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">Radius - Add NAS</div>
            <div class="panel-body">

                <form class="form-horizontal" method="post" role="form" action="{$_url}radius/nas-add-post">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Router_Name']}</label>
                        <div class="col-md-6">
                            <input type="text" required class="form-control" id="shortname" name="shortname" maxlength="32">
                            <p class="help-block">{Lang::T('Name of Area that router operated')}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['IP_Address']}</label>
                        <div class="col-md-6">
                            <input type="text" placeholder="192.168.88.1" required class="form-control" id="nasname"
                                name="nasname" maxlength="128">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Secret</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" id="secret" name="secret" required
                                onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'" maxlength="60">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Ports</label>
                        <div class="col-md-2">
                            <input type="text" class="form-control" id="ports" name="ports" placeholder="null">
                        </div>
                        <label class="col-md-2 control-label">Type</label>
                        <div class="col-md-2">
                            <input type="text" class="form-control" id="type" name="type" value="other" required
                                placeholder="other">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Server</label>
                        <div class="col-md-2">
                            <input type="text" class="form-control" id="server" name="server" placeholder="null">
                        </div>
                        <label class="col-md-2 control-label">Community</label>
                        <div class="col-md-2">
                            <input type="text" class="form-control" id="community" name="community" placeholder="null">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Description']}</label>
                        <div class="col-md-6">
                            <textarea class="form-control" id="description" name="description"></textarea>
                            <p class="help-block">{Lang::T('Explain Coverage of router')}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-primary waves-effect waves-light"
                                type="submit">{$_L['Save']}</button>
                            Or <a href="{$_url}radius/nas-list">{$_L['Cancel']}</a>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}