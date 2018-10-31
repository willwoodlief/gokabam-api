
class KabamDisplayTagWide extends KabamDisplayBase {


    constructor(gokabam,the_filter,container) {

        super(gokabam,the_filter,container,'wide',true,'KabamTag');
        this._parent_kid = null;
        let count = 0;
        for(let i = 0; i < the_filter.rules.length; i++) {
            let rule = the_filter.rules[i];
            if (rule.property_name === 'parent') {
                this._parent_kid = rule.property_value;
                count++;
            }
        }
        if (!this._parent_kid) {
            throw new Error("Tag container cannot find a parent id in the rules");
        }

        this.tokensfield = null;

    }

    get parent_kid() { return this._parent_kid;}

    create_parent_div(classes) {
        classes.push('gk-tag-wide my-clearfix');
        return  super.create_parent_div(classes);
    }

    create_content_div(parent) {
        return  super.create_content_div(parent);
    }

    // noinspection JSUnusedLocalSymbols
    add_child_containers(parent) {
        return []
    }

    on_refresh(parent_div) {

        let object_array = this.objects;


        if (this.tokensfield) {
            this.tokensfield.setItems(this.objects);
            return;
        }


        let that = this;
        parent_div.html('');
        let input_id = this.base_id + '_token_input';

        parent_div.append('<input class="' + input_id + '">');


        setTimeout(do_tokenfield, 500);

        function do_tokenfield() {
            that.tokensfield = new Tokenfield({
                el: document.querySelector('.' + input_id), // Attach Tokenfield to the input element with class "text-input"
                items: object_array,
                newItems: true,
                itemValue: 'kid',
                itemData: 'text',
                itemLabel: 'text'
            });

            that.tokensfield.on('addedToken' ,(a,token_info) => {
                if (token_info.hasOwnProperty('md5_checksum')) {
                    return; //we are just adding existing
                }
                let token_name = token_info.text;
                let k = new KabamTag(null);
                k.parent = that.parent_kid;
                k.text = token_name;
                that.gokabam.update([k]);
            });

            /**
             *
             * @param {KabamTag} kabam_tag_object
             * @return {void|string}
             */
            that.tokensfield.on("removedToken", (a,kabam_tag_object) => {
                if (kabam_tag_object.hasOwnProperty('md5_checksum')) {
                    let k = new KabamTag(kabam_tag_object);
                    k.delete = 1;
                    that.gokabam.update([k]);
                }

            });



            /**
             *
             * @param {KabamTag} kabam_tag_object
             * @return {void|string}
             */
            that.tokensfield.renderSetItemLabel = function(kabam_tag_object) {
                if (kabam_tag_object.hasOwnProperty('md5_checksum')) {
                    let val = '';
                    if (kabam_tag_object.value) {
                        val = '@' + kabam_tag_object.value
                    }
                    return kabam_tag_object.text + val;
                }
                return kabam_tag_object.text;
            };





            that.tokensfield.setItems(object_array);

            for(let i = 0 ; i < that.objects.length; i++) {
                let tag = that.objects[i];
                if (tag.value === 'blue') {
                    $("input:hidden[class='item-input'][value='"+ tag.kid +"']").closest('li').css('background-color', 'blue');
                }
            }
            //
        }


    }

    /**
     * @param {KabamEditorBase} editor
     */
    on_edit_starting(editor) {

    }

    /**
     * @param {KabamEditorBase} editor
     */
    on_edit_submit(editor) {

    }

    /**
     * @param {KabamEditorBase} editor
     */
    on_edit_cancel(editor) {

    }


}


