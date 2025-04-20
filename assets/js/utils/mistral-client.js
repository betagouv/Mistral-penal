import $ from 'jquery';

class MistralClient {

    postItem(url, data, isJson = false){
        let contentType='application/x-www-form-urlencoded; charset=UTF-8';
        if(isJson) contentType='application/ld+json';

        return new Promise((successCallback, failureCallback) => {
            $.ajax({
                type:"POST",
                url:url,
                data:data,
                contentType:contentType
            }).done(function (res) {
                successCallback(res);
            }).fail(function(err){
                failureCallback(err);
            });
        });
    }

    getItem(url, data){
        return $.get(url, data).done().fail();
    }

    getDecisionForm(){
        const url = Routing.generate('affaire_decision', {'id': $('#decision_affaire').val()});
        return $.get(url).done().fail();
    }

    deleteDecision(id){
        return $.ajax({
		url: Routing.generate('_api_/decisions/{id}{._format}_delete', {'id': id}),
            type: 'DELETE',
            success: function(response) {
            },
            fail: function(response){
            }
        });
    }

    deleteItem(id, url){
        const data = {'id': id};

        return $.ajax({
            url: Routing.generate(url, data),
            type: 'DELETE',
            success: function(response) {
            },
            fail: function(response){
            }
        });
    }
}

export default MistralClient;
