$(document).ready(function(){

    $('.info-planned').each(function() {
        issue = $(this).attr('issue');
        branch = $(this).attr('branch');
        //alert(issue +  branch);
    });

    data = {jsonrpc:"2.0",method:"allQueryNext",params:["tr:38604 branch:TYPO3_4-7","z",25],"id":5};
    data = JSON.stringify(data);
    $.post({
        url: 'https://review.typo3.org/gerrit/rpc/ChangeListService',
        data: data,
        success: function() {},
        dataType: 'json'
    });

});