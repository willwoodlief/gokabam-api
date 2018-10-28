
class KabamDisplayWordSingle extends KabamDisplayBase {


    constructor(gokabam,the_filter,container) {
        super(gokabam,the_filter,container);
        this._is_multiple = false;
        this._style = 'minimal';
        this._root_type = 'KabamWord';
    }

    create_parent_div(classes) {
        classes.push('gokabam-draw-word-single');
        super.create_parent_div(classes);
    }

    refresh() {
        let parent_div = this.div;
        parent_div.html('');
        let object_array = this.objects;
        for(let i =0; i < object_array.length ; i++) {
            /**
             * @type {KabamWord}
             */
            let word = object_array[i];
            let html =
                '<div class="gk-word">' +
                '<span class="gk-word-type">' + word.type + '</span><br> ' +
                '<span class="gk-word-language">' + word.language + '</span><br> ' +
                '<span class="gk-word-text">' + word.text + '</span><br> ' +
                '</div>';
            parent_div.append(html);
        }
    }


}


